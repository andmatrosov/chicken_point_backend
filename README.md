````md
# README.md

## Project

Mobile game backend built with Laravel.

This project provides:

- API authentication
- player profile
- best score tracking
- coins balance
- skins shop
- active skin selection
- leaderboard top 15
- prize assignment system
- admin panel
- basic API abuse protection layers

---

# 1. Features

## Player

- registration and login
- personal profile
- best score
- coins balance
- owned skins
- active skin
- current leaderboard rank
- personal prizes

## Game

- server-issued game session
- score submission through session token
- leaderboard top 15
- anti-duplicate score submission logic

## Shop

- list of skins
- prices
- purchase flow
- owned / active status

## Prizes

- prizes with quantity
- automatic prize assignment by leaderboard rank
- manual prize assignment by admin

## Admin panel

- manage users
- manage skins
- manage prizes
- review sessions and scores
- assign prizes
- review leaderboard

## Security

- Sanctum token auth
- route protection
- rate limiting
- signed request middleware
- nonce replay protection
- suspicious request logging

---

# 2. Tech stack

- PHP 8.3+
- Laravel
- MySQL 8+ or PostgreSQL
- Redis
- Laravel Sanctum
- Filament
- PHPUnit or Pest

---

# 3. Project structure

Suggested key folders:

```txt
app/
  Actions/
  Enums/
  Filament/
  Http/
    Controllers/
      Api/
    Requests/
    Resources/
  Jobs/
  Models/
  Policies/
  Services/
  Support/

config/
  game.php

database/
  factories/
  migrations/
  seeders/

routes/
  api.php
  web.php
```
````

---

# 4. Main domain entities

## User

Stores:

- email
- password
- best score
- coins
- active skin
- admin flag

## Skin

Stores:

- title
- code
- price
- image
- active flag

## UserSkin

Stores owned skins for each user.

## GameSession

Server-issued token for a play attempt.

## GameScore

Stores submitted scores history.

## Prize

Stores prize definition, stock, and default leaderboard rank mapping.

## UserPrize

Stores assigned prizes for players.

## AdminActionLog

Stores important admin actions.

---

# 5. Requirements

Before installation, make sure you have:

- PHP 8.3+
- Composer
- MySQL 8+ or PostgreSQL
- Redis
- Node.js and npm only if frontend assets are needed for admin theme build
- Git

---

# 6. Installation

## 6.1 Clone repository

```bash
git clone <repository-url> mobile-game-backend
cd mobile-game-backend
```

## 6.2 Install dependencies

```bash
composer install
```

## 6.3 Create environment file

```bash
cp .env.example .env
```

## 6.4 Generate app key

```bash
php artisan key:generate
```

## 6.5 Configure environment

Fill the main values in `.env`.

Example:

```env
APP_NAME="Mobile Game Backend"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_TIMEZONE=UTC

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=mobile_game
DB_USERNAME=root
DB_PASSWORD=

CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=database

REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=secret12345
```

If using PostgreSQL, replace DB settings accordingly.

---

# 7. Database setup

## 7.1 Run migrations

```bash
php artisan migrate
```

## 7.2 Seed demo data

```bash
php artisan db:seed
```

This should create:

- admin user
- demo users
- sample skins
- sample prizes
- demo leaderboard data

---

# 8. Local development

## 8.1 Start Laravel server

```bash
php artisan serve
```

## 8.2 Start queue worker

```bash
php artisan queue:work
```

## 8.3 Optional: run scheduler if later needed

```bash
php artisan schedule:work
```

---

# 9. Authentication

This project uses Laravel Sanctum for API token authentication.

## Auth endpoints

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`

## Auth flow

1. Register or log in.
2. Receive API token.
3. Send token in header:

```http
Authorization: Bearer <token>
```

---

# 10. API overview

## Auth

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`

## Profile

- `GET /api/profile`
- `GET /api/profile/skins`
- `POST /api/profile/active-skin`
- `GET /api/profile/rank`

## Game

- `POST /api/game/session/start`
- `POST /api/game/submit-score`
- `GET /api/game/leaderboard`

## Shop

- `GET /api/game/shop`
- `POST /api/game/shop/buy-skin`

## Prizes

- `GET /api/prizes/my`

---

# 11. Example requests

## 11.1 Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player1@example.com",
    "password": "secret12345",
    "password_confirmation": "secret12345"
  }'
```

## 11.2 Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player1@example.com",
    "password": "secret12345"
  }'
```

## 11.3 Get profile

```bash
curl http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>"
```

## 11.4 Start game session

```bash
curl -X POST http://localhost:8000/api/game/session/start \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

## 11.5 Submit score

```bash
curl -X POST http://localhost:8000/api/game/submit-score \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "SESSION_TOKEN_HERE",
    "score": 2450
  }'
```

## 11.6 List shop skins

```bash
curl http://localhost:8000/api/game/shop \
  -H "Authorization: Bearer <token>"
```

## 11.7 Buy skin

```bash
curl -X POST http://localhost:8000/api/game/shop/buy-skin \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "skin_id": 2
  }'
```

## 11.8 Get leaderboard

```bash
curl http://localhost:8000/api/game/leaderboard \
  -H "Authorization: Bearer <token>"
```

---

# 12. Standard response format

## Success

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

## Validation error

```json
{
  "success": false,
  "message": "Validation failed",
  "errors": {
    "field": ["The field is required."]
  }
}
```

## Business error

```json
{
  "success": false,
  "message": "Not enough coins"
}
```

---

# 13. Leaderboard rules

- leaderboard is based on `users.best_score`
- public leaderboard returns top 15
- public leaderboard must not expose full emails of other users
- use masked emails or nicknames in player-facing API
- rank must be calculated from score data, not treated as permanently stored truth
- tie-breaker must be deterministic

Example masked email:

- `alex@example.com` -> `al***@example.com`

---

# 14. Score submission rules

Important:
client cannot be trusted.

Because of that:

- score submission requires authenticated user
- score submission requires valid server-issued session token
- one session token can only be submitted once
- expired session tokens must be rejected
- server decides what to update
- client must never send final balance, rank, or owned items as truth

Recommended flow:

1. Player starts a game session.
2. Backend returns `session_token`.
3. Client plays match.
4. Client submits score with `session_token`.
5. Backend validates session and stores result.
6. Backend updates `best_score` if needed.

---

# 15. Shop rules

- only active skins are shown
- player cannot buy same skin twice
- player must have enough coins
- purchase must run inside DB transaction
- owned skins are stored in pivot table
- active skin must belong to current user

---

# 16. Prize rules

Two prize assignment modes are supported.

## Automatic assignment

Based on rank in current top 15.
Each prize can define:

- `default_rank_from`
- `default_rank_to`

Example:

- prize A -> rank 1
- prize B -> ranks 2 to 3
- prize C -> ranks 4 to 15

## Manual assignment

Admin can assign a prize to any player directly.

Rules:

- prize stock must be checked
- assignment should be logged
- user prize should store status and assignment metadata

---

# 17. Admin panel

This project uses Filament.

Admin panel should support:

- user list and editing
- skin management
- prize management
- user prize management
- score inspection
- session inspection
- leaderboard review
- prize assignment actions
- audit logs

## Admin access

Only admin users can access Filament panel.

Admin credentials are seeded using environment values:

- `ADMIN_EMAIL`
- `ADMIN_PASSWORD`

---

# 18. Security model

It is not possible to completely block direct HTTP requests from a reverse-engineered mobile app.
The real goal is to make abuse harder.

This project uses layered protection.

## 18.1 Authentication

Sensitive endpoints require Sanctum bearer token.

## 18.2 Rate limiting

Apply route-specific limits to:

- login
- register
- session start
- submit score
- buy skin

## 18.3 Signed requests

Optionally require:

- `X-Timestamp`
- `X-Nonce`
- `X-Signature`

Suggested signature payload:

```txt
METHOD|PATH|BODY|TIMESTAMP|NONCE
```

Suggested algorithm:

- HMAC-SHA256

## 18.4 Replay protection

- nonce must be unique during TTL
- timestamp must be recent
- reused nonce must be rejected

## 18.5 Server-side authority

Server must always calculate:

- coins balance
- best score updates
- owned items
- prize assignments
- leaderboard position

---

# 19. Redis usage

Redis is recommended for:

- rate limiting
- cache
- queue
- nonce storage
- replay protection

If Redis is unavailable, some features may need fallback handling, but Redis is recommended for production.

---

# 20. Testing

## Run all tests

```bash
php artisan test
```

or

```bash
vendor/bin/pest
```

## Recommended test coverage

- auth
- profile
- shop
- game sessions
- score submission
- leaderboard
- prizes
- request signature validation
- nonce replay protection

---

# 21. Useful artisan commands

## Clear app caches

```bash
php artisan optimize:clear
```

## Fresh migration with seeding

```bash
php artisan migrate:fresh --seed
```

## Run queue worker

```bash
php artisan queue:work
```

## Open Tinker

```bash
php artisan tinker
```

---

# 22. Common development notes

## Thin controllers

Do not place core business rules in controllers.

## Use services/actions

Recommended classes:

- `AuthService`
- `LeaderboardService`
- `ShopService`
- `PrizeService`
- `GameSessionService`
- `ScoreSubmissionService`
- `RequestSignatureService`

## Use Form Requests

Validate all incoming request data using dedicated request classes.

## Use API Resources

Do not return raw Eloquent models directly.

## Use transactions

Transactions are required for:

- skin purchase
- prize assignment
- score submission if several state updates occur

---

# 23. Example implementation order

Recommended development order:

1. bootstrap project
2. create schema and models
3. install and configure Sanctum
4. install and configure Filament
5. implement auth endpoints
6. implement profile endpoints
7. implement shop module
8. implement game session issuing
9. implement score submission
10. implement leaderboard
11. implement prize module
12. implement admin panel actions
13. implement signature middleware
14. implement tests
15. finalize docs

---

# 24. Production notes

Before deploying to production:

- set `APP_ENV=production`
- set `APP_DEBUG=false`
- use HTTPS only
- use secure database credentials
- use secure Redis configuration
- rotate admin credentials
- configure queue worker supervisor
- configure backups
- configure logging and monitoring
- verify rate limits
- verify signed request middleware where enabled

---

# 25. Known limitations

Important:
a mobile client cannot be made fully trusted.

Even with:

- token auth
- signed requests
- nonces
- timestamps
- replay protection

a modified client can still attempt abuse.

Because of that, final protection strategy should always rely on:

- server-side authority
- anomaly detection
- controlled business rules
- logs and moderation if needed

---

# 26. Next files to review

- `AGENTS.md`
- `TASKS.md`
- `config/game.php`
- `routes/api.php`

These documents define implementation structure and task order.

---

# 27. API Documentation (Swagger)

## Overview

Swagger / OpenAPI is integrated using `darkaonline/l5-swagger`.
The documentation source is centralized under `app/OpenApi` so it stays aligned with the real public API without turning controllers into annotation dumps.

## Documentation URL

- UI: `/api/documentation`
- Raw OpenAPI JSON: `/api/documentation/docs`

## Generate docs

```bash
php artisan l5-swagger:generate
```

## Authentication

### Bearer token (Sanctum)

Send the access token in the `Authorization` header:

```http
Authorization: Bearer <sanctum-token>
```

### Request signature

Signed mutation endpoints also require these headers:

- `X-Timestamp`
- `X-Nonce`
- `X-Signature`

Notes:

- the timestamp must be fresh
- the nonce must be unique within the replay-protection window
- the signature is an HMAC-SHA256 value derived from the request method, path, body, timestamp, and nonce

## Notes

- the Swagger docs reflect the real implemented API
- API responses use the standardized envelope: `success`, `data`, `meta`
