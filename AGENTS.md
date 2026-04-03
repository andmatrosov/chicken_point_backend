Ниже — готовая инструкция в формате AGENTS.md для Codex. Она разбита на этапы и ориентирована на реализацию Laravel-бекенда для мобильной игры с API, админкой, лидербордом, магазином, призами и базовой защитой от подделки запросов.

# AGENTS.md

## Project

Backend for a mobile game on Laravel.

## Goal

Build a production-ready backend API and admin panel for a mobile game with:

- user profile
- best score
- coins balance
- owned skins
- active skin
- leaderboard top 15
- shop with skins and prices
- prizes with quantity and assignment rules based on top-15 rank
- admin panel
- API protection against naive direct request abuse
- clean architecture suitable for future scaling

---

# 1. Core stack

Use:

- Laravel latest stable version
- PHP 8.3+
- MySQL 8+ or PostgreSQL
- Redis for cache, nonce storage, rate limiting, and queues
- Laravel Sanctum for API token auth
- Filament for admin panel
- Laravel Policies / Gates for permissions
- Form Requests for validation
- API Resources for response formatting
- Database transactions for critical business actions
- Queue jobs for delayed/recalculated actions
- Eloquent ORM
- PHPUnit or Pest for tests

---

# 2. General architecture rules

Follow these rules strictly:

1. Do not place business logic inside controllers.
2. Controllers must stay thin.
3. Put game logic into Services / Actions.
4. Use FormRequest for validation.
5. Use API Resource classes for all API responses.
6. Use DB transactions for:
   - score submission if it changes user state
   - skin purchase
   - prize assignment
7. Never trust client-calculated values.
8. Never accept from client:
   - final coins balance
   - owned skins list
   - rank
   - final leaderboard position
   - prize ownership as a fact
9. All important state must be calculated on the server.
10. Prefer explicit names over magic abstractions.

Recommended structure:

- `app/Actions`
- `app/Services`
- `app/Http/Controllers/Api`
- `app/Http/Requests`
- `app/Http/Resources`
- `app/Models`
- `app/Policies`
- `app/Filament`
- `app/Enums`
- `app/Jobs`

---

# 3. Main entities

Implement the following domain entities.

## 3.1 User

Fields:

- id
- email
- password
- best_score
- coins
- active_skin_id nullable
- last_rank_cached nullable
- is_admin boolean
- created_at
- updated_at

Rules:

- `best_score` stores the user’s best score for fast leaderboard access
- `coins` is the actual server-side balance
- `active_skin_id` must reference a skin already owned by the user
- `last_rank_cached` is optional cache only, never source of truth

---

## 3.2 Skin

Fields:

- id
- title
- code unique
- price
- image nullable
- is_active boolean
- sort_order nullable
- created_at
- updated_at

Rules:

- only active skins can appear in shop
- `code` is internal stable identifier
- `price` is integer, store in smallest unit if needed, but for coins integer is enough

---

## 3.3 UserSkin

Pivot table for owned skins.

Fields:

- id
- user_id
- skin_id
- purchased_at
- created_at
- updated_at

Rules:

- one user cannot own same skin twice
- add unique composite index on `(user_id, skin_id)`

---

## 3.4 GameScore

Store score history.

Fields:

- id
- user_id
- score
- session_token
- is_processed boolean
- created_at
- updated_at

Rules:

- one score row per submitted game result
- `session_token` links score to issued game session
- `is_processed` prevents duplicate business side effects if needed

---

## 3.5 GameSession

Server-issued play session for safer score submission.

Fields:

- id
- user_id
- token unique
- status enum: active, submitted, expired, canceled
- issued_at
- expires_at
- submitted_at nullable
- metadata nullable JSON
- created_at
- updated_at

Rules:

- a score may be submitted only for a valid active session
- one session token can be submitted only once
- session expires automatically after configured duration

---

## 3.6 Prize

Fields:

- id
- title
- description nullable
- quantity
- default_rank_from nullable
- default_rank_to nullable
- is_active boolean
- created_at
- updated_at

Rules:

- `quantity` = remaining stock or total stock depending on chosen logic
- if using remaining stock, decrement only when assigned
- rank mapping is used for automatic assignment
- allow prizes with no default rank for manual admin assignment

---

## 3.7 UserPrize

Fields:

- id
- user_id
- prize_id
- rank_at_assignment nullable
- assigned_manually boolean
- assigned_by nullable user_id
- assigned_at
- status enum: pending, issued, canceled
- created_at
- updated_at

Rules:

- prize assignment must respect stock
- must record whether assignment was automatic or manual

---

## 3.8 ApiNonce

Optional table if using DB instead of Redis.
Prefer Redis, but support DB fallback.

Fields:

- id
- user_id nullable
- nonce unique
- expires_at
- created_at
- updated_at

Used to prevent replay attacks.

---

## 3.9 AdminActionLog

Optional but recommended.

Fields:

- id
- admin_user_id
- action
- entity_type
- entity_id
- payload nullable JSON
- created_at
- updated_at

Use for audit trail.

---

# 4. Relationships

Implement relationships.

## User

- belongsTo activeSkin -> Skin
- belongsToMany skins through user_skins
- hasMany userSkins
- hasMany scores
- hasMany gameSessions
- hasMany userPrizes

## Skin

- hasMany userSkins
- belongsToMany users through user_skins

## Prize

- hasMany userPrizes

## UserPrize

- belongsTo user
- belongsTo prize
- belongsTo assignedBy -> User nullable

## GameScore

- belongsTo user

## GameSession

- belongsTo user

---

# 5. API modules

Create the following API modules.

## 5.1 Auth

Endpoints:

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`

Requirements:

- use Sanctum token auth
- token must be required for all protected routes
- on login optionally bind token to device info:
  - device_id
  - platform
  - app_version

---

## 5.2 Profile

Endpoints:

- `GET /api/profile`
- `GET /api/profile/skins`
- `POST /api/profile/active-skin`
- `GET /api/profile/rank`
- `GET /api/profile/prizes`

Profile response should include:

- email
- best_score
- coins
- active_skin
- owned_skins_count
- current_rank
- leaderboard_top15_flag optional

---

## 5.3 Leaderboard

Endpoints:

- `GET /api/game/leaderboard`

Response:

- top 15 players
- each entry:
  - rank
  - masked_email or nickname
  - score
- current user rank separately if authenticated

Rules:

- never expose full email to other players
- use masked email or username
- ranking source is `users.best_score`
- tie-breaker must be deterministic, e.g.:
  1. best_score desc
  2. earliest score achievement date asc or user id asc

Implement service for leaderboard calculation.

---

## 5.4 Game session and score submission

Endpoints:

- `POST /api/game/session/start`
- `POST /api/game/submit-score`

`session/start` returns:

- session_token
- expires_at

`submit-score` accepts:

- session_token
- score
- optional metadata:
  - duration
  - coins_collected
  - app_version
  - device_id
  - anti_fraud payload if needed

Rules:

- session token must exist
- belong to current user
- be active
- not expired
- not already submitted

On successful submit:

1. create `game_scores` row
2. mark session submitted
3. if score > user.best_score then update it
4. if gameplay grants coins, calculate coins server-side according to rules
5. return updated profile summary

Do not accept:

- direct best score replacement without session
- client-sent final coins balance

---

## 5.5 Shop

Endpoints:

- `GET /api/game/shop`
- `POST /api/game/shop/buy-skin`

Shop response:

- list of active skins
- price
- is_owned
- is_active_for_user

Purchase rules:

1. authenticated user only
2. skin must exist and be active
3. user must not already own it
4. user must have enough coins
5. purchase must run in DB transaction
6. decrement coins
7. create `user_skins` row
8. optionally auto-set active skin if it is the first owned skin

---

## 5.6 Prizes

Endpoints:

- `GET /api/prizes/my`

Admin-only actions handled via admin panel or protected admin API.

Rules:

- normal user can view only own assigned prizes
- prize status visible in response

---

# 6. Admin panel

Use Filament.

Create admin resources for:

- Users
- Skins
- Prizes
- UserPrizes
- Leaderboard view
- GameScores read-only
- GameSessions read-only
- AdminActionLogs read-only

## 6.1 Users admin

Admin should be able to:

- view users
- search by email
- edit coins
- edit best_score only if absolutely needed
- view owned skins
- view assigned prizes
- grant/remove admin rights if required

## 6.2 Skins admin

Admin should be able to:

- create skin
- edit title/code/price/image/active flag
- sort skins
- deactivate skins without deleting historical ownership

## 6.3 Prizes admin

Admin should be able to:

- create prize
- edit title/description/quantity/default rank range
- activate/deactivate prize
- see current assignments

## 6.4 Prize assignment admin

Need actions:

- assign selected prize to selected user manually
- auto-assign prizes based on current top-15
- preview which users receive what before confirming
- prevent over-assignment if quantity is insufficient

## 6.5 Leaderboard admin

Need:

- current top-15 table
- score
- full email
- user id
- prize assignment status

---

# 7. Security requirements

Important: impossible to fully prevent direct request sending from a mobile app.
Goal is to reduce abuse and make trivial cheating harder.

Implement these layers.

## 7.1 Auth

- use Laravel Sanctum
- all sensitive endpoints require Bearer token
- do not use user_id from request body as identity source
- identity comes only from authenticated token

## 7.2 HTTPS

- assume HTTPS only
- reject insecure deployment configs

## 7.3 Rate limiting

Configure route-specific rate limits:

Examples:

- login: strict
- register: strict
- session/start: moderate
- submit-score: strict
- buy-skin: strict
- profile: moderate

Use Laravel rate limiter.

Suggested examples:

- login: 5/minute per IP + email
- register: 3/minute per IP
- submit-score: 20/minute per user
- buy-skin: 10/minute per user

Tune later.

## 7.4 Request signature

Add optional signed request middleware for mobile app.

Headers:

- `X-Timestamp`
- `X-Nonce`
- `X-Signature`

Signature algorithm:

- HMAC-SHA256
- payload: `METHOD|PATH|BODY|TIMESTAMP|NONCE`

Rules:

- timestamp must be fresh, e.g. within 60 seconds
- nonce must be unique during TTL window
- store nonce in Redis
- reject reused nonce
- compare signature using constant-time comparison

Important:

- this is an additional layer only
- do not assume mobile secret is perfectly safe forever

## 7.5 Replay protection

For critical endpoints:

- submit-score
- buy-skin
- prize operations if exposed by API

Require:

- nonce
- timestamp
- unique processing token or idempotency key where useful

## 7.6 Server-side validation only

Never trust client-side claims.

Examples:

- do not accept "new balance"
- do not accept "purchased=true"
- do not accept "current rank"
- do not accept "prize earned"

## 7.7 Anti-fraud for score submission

Implement basic heuristics:

- require active server-issued game session
- one session = one submit
- reject impossible score ranges if defined
- reject obviously invalid durations if provided
- log suspicious submissions
- optionally flag for review if:
  - too many high scores in short period
  - repeated near-identical metadata
  - score progression is statistically abnormal

Do not try to make client fully trusted.

## 7.8 Logging suspicious activity

Log:

- failed signatures
- reused nonce
- expired timestamps
- too many score submissions
- repeated invalid session tokens
- suspicious score patterns

---

# 8. Ranking logic

Do not permanently store rank as source of truth.

Ranking source:

- `users.best_score`

Top-15 query:

- order by `best_score desc`
- deterministic tie-breaker

Current user rank:

- calculate by query or cached service
- `last_rank_cached` may be stored as optimization only

Create a dedicated `LeaderboardService`.

Service responsibilities:

- fetch top 15
- compute current user rank
- mask emails for public output
- return admin-safe and player-safe variants

---

# 9. Prize assignment logic

Implement two assignment modes.

## 9.1 Automatic assignment by leaderboard rank

Admin action:

- "Assign prizes for current top-15"

Flow:

1. fetch current top-15
2. for each user rank determine matching prize rule
3. verify prize is active
4. verify prize quantity > 0
5. verify user does not already have same assignment if duplicate prevention required
6. create `user_prizes`
7. decrement prize quantity if using remaining stock model
8. log admin action

Need preview mode before confirmation.

## 9.2 Manual assignment

Admin can:

- select user
- select prize
- assign manually
- optionally set note or rank_at_assignment
- decrement stock
- log action

---

# 10. Response format

All API responses must be standardized.

Suggested JSON structure:

## success

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

validation error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["error text"]
  }
}
```

business error

```json
{
  "success": false,
  "message": "Not enough coins"
}
```

Use API Resources and a consistent response helper if needed.

⸻

# 11. Data masking and privacy

Important:

- do not expose other players’ full emails in public leaderboard
- admin panel may show full email
- API leaderboard for players must return masked email or nickname

Mask examples:

- alex@example.com -> al\*\*\*@example.com
- or create username system later

⸻

# 12. Migrations and indexes

Create migrations with proper indexes.

Required indexes:

- users.email unique
- skins.code unique
- user_skins unique (user_id, skin_id)
- game_sessions.token unique
- game_scores.user_id index
- game_scores.score index if needed
- user_prizes.user_id index
- prizes.is_active index
- users.best_score index

If leaderboard query becomes heavy, optimize later.

⸻

# 13. Seeders

Create seeders for local/dev.

Seed:

- admin user
- test users
- sample skins
- sample prizes
- random score data for leaderboard testing

Need one known admin account:

- email from env or dev config
- password from env or dev config

⸻

# 14. Config

Add dedicated config values.

Examples:

- game session TTL
- signature max skew seconds
- nonce TTL
- anti-fraud score limits
- leaderboard size default = 15

Create config file:

- config/game.php

⸻

# 15. Testing

Write tests for critical paths.

Required feature tests

- register
- login
- logout
- profile fetch
- shop list
- buy skin success
- buy skin insufficient coins
- buy skin duplicate ownership
- start game session
- submit score success
- submit score duplicate session rejection
- submit score expired session rejection
- leaderboard top 15
- current user rank visibility
- prizes visible only to owner
- request signature valid
- request signature invalid
- nonce replay rejected

Required unit tests

- leaderboard rank calculation
- prize mapping by rank
- email masking
- anti-fraud validation rules
- skin purchase service

⸻

# 16. Implementation steps

Follow these steps in order.

## Step 1. Bootstrap project

    -	create Laravel project
    -	configure DB
    -	configure Redis
    -	install Sanctum
    -	install Filament
    -	configure API routes
    -	configure auth middleware
    -	create base folders for Actions/Services/Resources/Requests

## Step 2. Create database schema

Create migrations and models for:

- users
- skins
- user_skins
- game_scores
- game_sessions
- prizes
- user_prizes
- admin_action_logs optional

Then add relationships.

## Step 3. Implement authentication

    -	register
    -	login
    -	logout
    -	me endpoint
    -	API token issuing with Sanctum

## Step 4. Implement profile module

    -	profile endpoint
    -	skins list for current user
    -	active skin change
    -	current rank endpoint

## Step 5. Implement shop

    -	list skins
    -	buy skin action with transaction
    -	update active skin if needed

## Step 6. Implement game sessions and score submit

    -	issue session token
    -	submit score
    -	update best score
    -	optional coin reward calculation
    -	prevent duplicate submission

## Step 7. Implement leaderboard

    -	top 15
    -	masked email
    -	current user rank
    -	service abstraction

## Step 8. Implement prizes

    -	own prizes endpoint
    -	prize models and statuses
    -	assignment logic services

## Step 9. Implement admin panel

## Create Filament resources:

    -	users
    -	skins
    -	prizes
    -	user prizes
    -	scores
    -	sessions
    -	leaderboard page
    -	actions for auto-assigning prizes

## Step 10. Implement security layer

    -	rate limiting
    -	signature middleware
    -	nonce store
    -	replay protection
    -	suspicious activity logging

## Step 11. Implement tests

Cover all critical user flows and business logic.

## Step 12. Cleanup and documentation

    -	final refactor
    -	ensure controllers are thin
    -	add docblocks only where useful
    -	write README
    -	add API examples

⸻

# 17. Suggested services and actions

Implement classes similar to these.

Services

- AuthService
- LeaderboardService
- ShopService
- PrizeService
- GameSessionService
- ScoreSubmissionService
- RequestSignatureService

Actions

- RegisterUserAction
- LoginUserAction
- BuySkinAction
- StartGameSessionAction
- SubmitScoreAction
- AssignPrizeByRankAction
- AssignPrizeManuallyAction

⸻

# 18. Suggested API Resources

Create resources for:

- UserProfileResource
- SkinResource
- OwnedSkinResource
- LeaderboardEntryResource
- LeaderboardResource
- PrizeResource
- UserPrizeResource

⸻

# 19. Suggested Form Requests

Create requests for:

- RegisterRequest
- LoginRequest
- SetActiveSkinRequest
- BuySkinRequest
- StartGameSessionRequest if metadata needed
- SubmitScoreRequest

Validation examples:

- score must be integer and >= 0
- skin_id must exist
- session_token must be string and exist format-wise
- email must be valid
- password min length defined in config

⸻

# 20. Code quality rules

    1.	Prefer explicit service methods.
    2.	Do not use facades everywhere when DI is cleaner.
    3.	Keep controller methods short.
    4.	Keep naming stable and obvious.
    5.	Avoid premature abstraction.
    6.	Use enums for statuses where possible.
    7.	Keep public API responses consistent.
    8.	Add transactions only where state integrity matters.
    9.	Add tests for every bug fixed if possible.

⸻

# 21. Non-goals for MVP

Do not implement unless needed later:

- social auth
- chat
- achievements
- clans/guilds
- multiple currencies
- seasonal resets
- push notifications
- real-time websockets
- deep analytics dashboard

Keep MVP focused.

⸻

# 22. Deliverables

The final implementation must include:

- working Laravel API
- working auth with Sanctum
- migrations and seeders
- leaderboard top 15
- shop and skin purchase flow
- score submission through server-issued sessions
- prizes and assignment logic
- Filament admin panel
- request signature middleware
- rate limiting
- tests
- README with setup instructions
- API endpoint documentation

⸻

# 23. Important product decisions

Use these decisions unless explicitly changed: 1. Laravel is the main backend framework. 2. Filament is used for admin panel. 3. Sanctum is used for API auth. 4. Leaderboard is based on users.best_score. 5. Public leaderboard does not expose full emails. 6. Score submission requires server-issued game session token. 7. Rank is computed, not manually stored as truth. 8. Prize assignment supports both automatic and manual modes. 9. API protection is layered, not based on a fake promise of blocking all direct requests. 10. Business logic lives in services/actions, not controllers.

⸻

# 24. First concrete implementation task for Codex

Start by generating: 1. project folder structure 2. migrations 3. models with relationships 4. Sanctum auth setup 5. Filament admin installation 6. initial API route groups 7. config/game.php 8. seeders for admin, skins, prizes, and demo users

After that, implement modules in this order:

- auth
- profile
- shop
- sessions
- submit score
- leaderboard
- prizes
- security middleware
- tests
