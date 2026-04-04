# TASKS.md

## Project

Laravel backend for a mobile game.

## Working format

This file is a practical implementation checklist.
Move through tasks in order.
Do not skip foundational setup.
After finishing each block:

- verify functionality manually
- run tests
- refactor only if needed
- commit changes in small logical commits

---

# 0. Global implementation rules

- Keep controllers thin.
- Put business logic into Services / Actions.
- Use FormRequest validation.
- Use API Resources for all API responses.
- Use DB transactions for critical state changes.
- Never trust client-calculated values.
- Never expose full emails in public leaderboard.
- Do not treat stored rank as source of truth.
- All protected API routes must require auth unless explicitly public.
- Security is layered: auth + rate limits + signed requests + replay protection + server-side validation.

---

# 1. Initial project bootstrap

## 1.1 Create project

- [x] Create new Laravel project
- [x] Configure `.env`
- [ ] Set app name
- [x] Set app URL
- [x] Configure timezone
- [x] Configure locale if needed

## 1.2 Configure infrastructure

- [x] Connect database
- [x] Configure Redis
- [x] Configure queue driver
- [x] Configure cache driver
- [x] Configure session driver if needed
- [x] Verify DB connection works
- [ ] Verify Redis connection works

## 1.3 Install main packages

- [x] Install Laravel Sanctum
- [x] Install Filament
- [x] Publish needed configs and migrations
- [x] Run package-specific setup commands

## 1.4 Prepare code structure

- [x] Create `app/Actions`
- [x] Create `app/Services`
- [x] Create `app/Enums`
- [x] Create `app/Http/Controllers/Api`
- [x] Create `app/Http/Requests`
- [x] Create `app/Http/Resources`
- [x] Create `app/Jobs`
- [ ] Create `app/Support` if needed
- [x] Create `config/game.php`

## 1.5 Base quality setup

- [ ] Configure formatter / code style
- [ ] Configure static analysis if used
- [x] Configure test environment
- [x] Create base README skeleton

---

# 2. Database schema

## 2.1 Users table updates

- [x] Add `best_score`
- [x] Add `coins`
- [x] Add `active_skin_id`
- [x] Add `last_rank_cached`
- [x] Add `is_admin`
- [x] Add needed indexes
- [x] Add foreign key for `active_skin_id` if appropriate

## 2.2 Skins

- [x] Create `skins` migration
- [x] Add fields:
  - [x] `title`
  - [x] `code`
  - [x] `price`
  - [x] `image`
  - [x] `is_active`
  - [x] `sort_order`
- [x] Add unique index for `code`

## 2.3 User skins

- [x] Create `user_skins` migration
- [x] Add fields:
  - [x] `user_id`
  - [x] `skin_id`
  - [x] `purchased_at`
- [x] Add unique composite index `(user_id, skin_id)`

## 2.4 Game sessions

- [x] Create `game_sessions` migration
- [x] Add fields:
  - [x] `user_id`
  - [x] `token`
  - [x] `status`
  - [x] `issued_at`
  - [x] `expires_at`
  - [x] `submitted_at`
  - [x] `metadata`
- [x] Add unique index for `token`
- [x] Expire stale active sessions before enforcing active-session limit

## 2.5 Game scores

- [x] Create `game_scores` migration
- [x] Add fields:
  - [x] `user_id`
  - [x] `score`
  - [x] `session_token`
  - [x] `is_processed`
- [x] Add indexes for `user_id`
- [x] Add indexes for score-related queries if needed
- [x] Enforce unique `session_token` at DB level
- [x] Stop trusting client-provided score metadata for coin balance changes

## 2.6 Prizes

- [x] Create `prizes` migration
- [x] Add fields:
  - [x] `title`
  - [x] `description`
  - [x] `quantity`
  - [x] `default_rank_from`
  - [x] `default_rank_to`
  - [x] `is_active`
- [x] Validate auto-assignment rank ranges
- [x] Enforce prize rank validation outside Filament page hooks

## 2.7 User prizes

- [x] Create `user_prizes` migration
- [x] Add fields:
  - [x] `user_id`
  - [x] `prize_id`
  - [x] `rank_at_assignment`
  - [x] `assigned_manually`
  - [x] `assigned_by`
  - [x] `assigned_at`
  - [x] `status`
- [x] Add indexes for active prize lookup and stock checks

## 2.8 Admin action logs

- [x] Create `admin_action_logs` migration
- [x] Add fields:
  - [x] `admin_user_id`
  - [x] `action`
  - [x] `entity_type`
  - [x] `entity_id`
  - [x] `payload`

## 2.9 Optional nonce table fallback

- [ ] Decide whether DB fallback for nonces is needed
- [ ] If yes, create `api_nonces` migration

## 2.10 Run migrations

- [x] Run all migrations
- [x] Check schema
- [x] Verify foreign keys and indexes

---

# 3. Models and relationships

## 3.1 Create models

- [x] `User`
- [x] `Skin`
- [x] `UserSkin`
- [x] `GameSession`
- [x] `GameScore`
- [x] `Prize`
- [x] `UserPrize`
- [x] `AdminActionLog`

## 3.2 Add casts

- [x] Boolean casts
- [x] Datetime casts
- [x] JSON casts
- [x] Integer casts where useful

## 3.3 Define relationships

### User

- [x] `activeSkin`
- [x] `skins`
- [x] `userSkins`
- [x] `scores`
- [x] `gameSessions`
- [x] `userPrizes`

### Skin

- [x] `userSkins`
- [x] `users`

### GameSession

- [x] `user`

### GameScore

- [x] `user`

### Prize

- [x] `userPrizes`

### UserPrize

- [x] `user`
- [x] `prize`
- [x] `assignedBy`

## 3.4 Guard/fillable review

- [x] Review mass assignment protection
- [x] Avoid unsafe fillable configuration

---

# 4. Enums and config

## 4.1 Create enums

- [x] `GameSessionStatus`
- [x] `UserPrizeStatus`

## 4.2 Add game config

Create `config/game.php` and add:

- [x] session TTL
- [x] signature max skew
- [x] nonce TTL
- [x] leaderboard size
- [x] anti-fraud thresholds
- [x] default rate limit values if needed

---

# 5. Seeders and demo data

## 5.1 Default bootstrap seed

- [x] Keep `DatabaseSeeder` minimal and deterministic
- [x] Seed exactly one local non-admin user for smoke testing
- [x] Document seeded credentials honestly in README

## 5.2 Bootstrap consistency

- [x] Remove undocumented env-driven admin bootstrap claims
- [x] Align `.env.example` with real runtime config
- [x] Verify `db:seed` behavior with a focused test

---

# 6. Authentication module

## 6.1 Sanctum setup

- [x] Publish Sanctum config if needed
- [x] Configure token auth
- [x] Ensure protected routes use Sanctum middleware

## 6.2 Requests

- [x] Create `RegisterRequest`
- [x] Create `LoginRequest`

## 6.3 Actions/services

- [x] Create `RegisterUserAction`
- [x] Create `LoginUserAction`
- [x] Create `AuthService` if needed

## 6.4 Controllers

- [x] Create auth controller
- [x] Add `register`
- [x] Add `login`
- [x] Add `logout`
- [x] Add `me`

## 6.5 Routes

- [x] Add `/api/auth/register`
- [x] Add `/api/auth/login`
- [x] Add `/api/auth/logout`
- [x] Add `/api/me`

## 6.6 Response resources

- [x] Create user auth/profile resource if needed

## 6.7 Manual tests

- [ ] Register works
- [ ] Login works
- [ ] Token is returned
- [ ] Logout revokes token
- [ ] Me endpoint works

---

# 7. Profile module

## 7.1 Requests

- [x] Create `SetActiveSkinRequest`

## 7.2 Services/actions

- [x] Create action/service for changing active skin
- [x] Create action/service for current rank retrieval

## 7.3 Resources

- [x] Create `UserProfileResource`
- [x] Create `OwnedSkinResource`

## 7.4 Controller

- [x] Create profile controller
- [x] Add `profile`
- [x] Add `skins`
- [x] Add `setActiveSkin`
- [x] Add `rank`
- [ ] Add `myPrizes` endpoint hook if kept in profile module

## 7.5 Routes

- [x] Add `/api/profile`
- [x] Add `/api/profile/skins`
- [x] Add `/api/profile/active-skin`
- [x] Add `/api/profile/rank`

## 7.6 Rules verification

- [x] Active skin can only be owned skin
- [x] Inactive/unowned skins cannot be set
- [x] Profile includes best score, coins, active skin, rank

---

# 8. Shop module

## 8.1 Requests

- [x] Create `BuySkinRequest`

## 8.2 Services/actions

- [x] Create `ShopService`
- [x] Create `BuySkinAction`

## 8.3 Resources

- [x] Create `SkinResource`

## 8.4 Controller

- [x] Create shop controller
- [x] Add `index`
- [x] Add `buy`

## 8.5 Routes

- [x] Add `/api/game/shop`
- [x] Add `/api/game/shop/buy-skin`

## 8.6 Business rules

- [x] Only active skins appear in shop
- [x] Owned skins marked correctly
- [x] Active-for-user flag works
- [x] Purchase uses DB transaction
- [x] Purchase validates current skin state inside transaction
- [x] Duplicate purchase blocked
- [x] Not enough coins blocked
- [x] Coins deducted correctly
- [x] Ownership saved correctly
- [x] First skin may become active automatically if desired

## 8.7 Manual tests

- [ ] Can list skins
- [ ] Can buy valid skin
- [ ] Cannot buy already owned skin
- [ ] Cannot buy expensive skin without balance

---

# 9. Game session module

## 9.1 Requests

- [x] Create `StartGameSessionRequest` if metadata is required

## 9.2 Services/actions

- [x] Create `GameSessionService`
- [x] Create `StartGameSessionAction`

## 9.3 Controller

- [x] Create game session controller
- [x] Add `start`

## 9.4 Routes

- [x] Add `/api/game/session/start`

## 9.5 Rules

- [x] Session token generated securely
- [x] Token is unique
- [x] Session tied to current user
- [x] Session expiry set correctly
- [x] Session metadata stored if required

## 9.6 Manual tests

- [ ] Authenticated user can start session
- [ ] Unauthenticated user cannot
- [ ] Session token returned correctly

---

# 10. Score submission module

## 10.1 Requests

- [x] Create `SubmitScoreRequest`

## 10.2 Services/actions

- [x] Create `ScoreSubmissionService`
- [x] Create `SubmitScoreAction`

## 10.3 Controller

- [x] Create score controller
- [x] Add `submit`

## 10.4 Routes

- [x] Add `/api/game/submit-score`

## 10.5 Core rules

- [x] Score submit requires auth
- [x] Score submit requires valid session token
- [x] Session must belong to current user
- [x] Session must be active
- [x] Session must not be expired
- [x] Session must not be submitted already
- [x] GameScore row created
- [x] Session marked submitted
- [x] User best score updated only if new score is higher
- [x] Ignore client-provided coin metadata during score submission
- [x] Final response returns updated summary

## 10.6 Anti-abuse rules

- [x] Reject duplicate session submission
- [x] Reject obviously invalid scores if limits exist
- [x] Reject obviously invalid metadata if used
- [x] Log rejected score submissions

## 10.7 Manual tests

- [ ] Valid session can submit score
- [ ] Same session cannot submit twice
- [ ] Expired session rejected
- [ ] Best score updates only when appropriate

---

# 11. Leaderboard module

## 11.1 Service

- [x] Create `LeaderboardService`

## 11.2 Resource

- [x] Create `LeaderboardEntryResource`
- [x] Create `LeaderboardResource`

## 11.3 Controller

- [x] Create leaderboard controller
- [x] Add `index`

## 11.4 Routes

- [x] Add `/api/game/leaderboard`
- [x] Allow public guest access to `/api/game/leaderboard`

## 11.5 Ranking rules

- [x] Rank based on `users.best_score`
- [x] Deterministic tie-breaker implemented
- [x] Top 15 query works
- [x] Guest leaderboard response contains only public data
- [x] Current user rank returned separately when authenticated
- [x] Full emails hidden from public response
- [x] Masking format is consistent

## 11.6 Performance

- [x] Add indexes for leaderboard query
- [ ] Consider caching if needed
- [x] Avoid N+1 queries

## 11.7 Manual tests

- [ ] Top 15 is correct
- [ ] Ranking order is correct
- [ ] User rank is correct
- [ ] Emails are masked

---

# 12. Prize module

## 12.1 Resources

- [x] Create `PrizeResource`
- [x] Create `UserPrizeResource`

## 12.2 Services/actions

- [x] Create `PrizeService`
- [x] Create `AssignPrizeByRankAction`
- [x] Create `AssignPrizeManuallyAction`

## 12.3 User-facing controller

- [x] Create prizes controller
- [x] Add `myPrizes`

## 12.4 Routes

- [x] Add `/api/prizes/my`

## 12.5 User-facing rules

- [x] User sees only own prizes
- [x] Prize status returned
- [x] No leakage of other user data

---

# 13. Prize auto-assignment logic

## 13.1 Core flow

- [x] Fetch current top 15
- [x] Determine prize by rank range
- [x] Check prize is active
- [x] Check quantity available
- [x] Prevent invalid duplicate assignment if required
- [x] Create `user_prizes`
- [x] Decrement stock if using remaining-stock logic
- [x] Log admin action

## 13.2 Preview mode

- [x] Build preview service/method for admin
- [x] Show which users would get which prizes
- [x] Show insufficient stock warnings

## 13.3 Manual verification

- [ ] Rank 1 gets correct prize
- [ ] Rank range mapping works
- [ ] Stock decrements correctly
- [ ] No assignment when stock is empty

---

# 14. Admin panel with Filament

## 14.1 Access control

- [x] Restrict admin panel to admins only
- [ ] Verify non-admin users cannot enter panel

## 14.2 User resource

- [x] Create Filament User resource
- [x] Add list/search/edit
- [x] Show coins
- [x] Show best score
- [x] Show active skin
- [x] Show owned skins relation
- [x] Show prizes relation

## 14.3 Skin resource

- [x] Create Skin resource
- [x] Add create/edit/delete policy decisions
- [x] Add active flag
- [x] Add sorting
- [x] Preserve old ownership records

## 14.4 Prize resource

- [x] Create Prize resource
- [x] Add title/description/quantity/rank range fields
- [x] Add active flag
- [x] Show current assignments

## 14.5 UserPrize resource

- [x] Create UserPrize resource
- [x] Show status
- [x] Show assignment metadata
- [x] Remove unsafe direct status editing
- [x] Use explicit safe transition actions only

## 14.6 GameScore view

- [x] Create read-only GameScore resource/page

## 14.7 GameSession view

- [x] Create read-only GameSession resource/page

## 14.8 Leaderboard admin page

- [x] Create custom admin page for leaderboard
- [x] Show full email
- [x] Show user id
- [x] Show best score
- [x] Show rank
- [x] Show prize assignment status
- [x] Use the same preview snapshot for leaderboard prize confirmation

## 14.9 Prize assignment admin actions

- [x] Add manual assign action
- [x] Add auto-assign action
- [x] Add preview action
- [x] Add stock validation messaging

## 14.10 Admin action logs

- [x] Create read-only AdminActionLog resource/page

## 14.11 Prize lifecycle admin actions

- [x] Add prize assignment cancellation action
- [x] Restrict cancellation to pending assignments only
- [x] Add issued transition for pending assignments only
- [x] Keep stock unchanged when marking assignments as issued
- [x] Restore stock safely on valid cancellation only
- [x] Add destructive prize deletion with assignment cleanup
- [x] Log prize cancellation and deletion actions

---

# 15. API response standardization

## 15.1 Common response format

- [x] Define success response shape
- [x] Define validation error shape
- [x] Define business error shape
- [x] Apply consistently across controllers

## 15.2 Resources

- [x] Ensure all main endpoints use resources
- [x] Avoid raw model dumps

## 15.3 Error handling

- [x] Customize exception handling if needed
- [x] Return clear messages for business errors

---

# 16. Security layer

## 16.1 Route protection

- [x] Ensure all sensitive endpoints require auth
- [x] Ensure admin endpoints require admin auth
- [x] Review route groups and middleware

## 16.2 Rate limiting

- [x] Configure login rate limit
- [x] Configure register rate limit
- [x] Configure active-skin rate limit
- [x] Configure session/start rate limit
- [x] Configure submit-score rate limit
- [x] Configure buy-skin rate limit
- [x] Configure profile fetch rate limit if needed

## 16.3 Signature middleware

- [x] Create `RequestSignatureService`
- [x] Create signature verification middleware
- [x] Require headers:
  - [x] `X-Timestamp`
  - [x] `X-Nonce`
  - [x] `X-Signature`
- [x] Verify timestamp skew
- [x] Verify nonce uniqueness
- [x] Verify HMAC signature
- [x] Use constant-time comparison
- [x] Apply signed request protection to authenticated mutation routes

## 16.4 Nonce storage

- [x] Implement Redis nonce storage
- [x] Add TTL handling
- [ ] Add fallback strategy if needed

## 16.5 Replay protection

- [x] Reject reused nonces
- [ ] Log replay attempts
- [x] Apply to critical endpoints

## 16.6 Suspicious activity logging

- [x] Log failed signatures
- [x] Log expired timestamps
- [x] Log nonce replays
- [x] Log invalid session submit attempts
- [x] Log invalid score submissions

## 16.7 Deployment safety checks

- [x] Verify production uses HTTPS
- [x] Verify debug mode off in production
- [x] Verify secure env handling

---

# 17. Policies and permissions

## 17.1 Define access rules

- [x] Admin-only access for admin panel
- [x] Admin-only access for manual prize assignment
- [x] Admin-only access for auto prize assignment
- [x] User-only access to own profile data
- [x] User-only access to own prizes

## 17.2 Implement policies/gates

- [x] Create gates or policies where useful
- [x] Apply them in controllers/admin actions

---

# 18. Logging and audit

## 18.1 Admin action logging

- [x] Log prize assignment actions
- [x] Log manual user balance edits if allowed
- [x] Log admin data changes if needed

## 18.2 System logs

- [x] Log suspicious requests
- [x] Log important business failures
- [x] Avoid logging secrets/tokens in plaintext

---

# 19. Testing

## 19.1 Feature tests: auth

- [x] Register success
- [x] Login success
- [x] Login invalid credentials
- [x] Logout success
- [x] Me endpoint requires auth

## 19.2 Feature tests: profile

- [x] Profile fetch success
- [x] Owned skins endpoint success
- [x] Set active skin success
- [x] Set unowned skin fails

## 19.3 Feature tests: shop

- [x] Shop list success
- [x] Buy skin success
- [x] Buy skin insufficient coins
- [x] Buy skin duplicate ownership
- [x] Buy inactive skin fails

## 19.4 Feature tests: sessions and scores

- [x] Start session success
- [x] Submit score success
- [x] Submit score duplicate session rejected
- [x] Submit score expired session rejected
- [x] Submit score wrong-owner session rejected
- [x] Best score updates correctly

## 19.5 Feature tests: leaderboard

- [x] Top 15 returned
- [x] Guest leaderboard works without authentication
- [x] Order is correct
- [x] Current user rank returned
- [x] Emails are masked

## 19.6 Feature tests: prizes

- [x] My prizes returns only own prizes
- [x] Auto assignment works
- [x] Manual assignment works if admin API/action tested
- [x] Out-of-stock assignment blocked

## 19.7 Feature tests: security

- [x] Valid signature accepted
- [x] Invalid signature rejected
- [x] Reused nonce rejected
- [x] Expired timestamp rejected
- [x] Rate limit triggered correctly

## 19.8 Unit tests

- [x] Email masking
- [x] Rank calculation
- [x] Tie-breaker logic
- [x] Prize mapping by rank
- [x] Purchase service rules
- [x] Score submission service rules
- [x] Signature service logic

---

# 20. Manual QA checklist

## 20.1 Auth

- [x] Register through API client
- [x] Login through API client
- [x] Use token in protected request

## 20.2 Game flow

- [x] Start game session
- [x] Submit score
- [x] Check leaderboard update
- [x] Check profile update

## 20.3 Shop flow

- [x] View skins
- [x] Buy skin
- [x] Set active skin
- [x] Verify coins changed correctly

## 20.4 Prize flow

- [x] Check my prizes endpoint
- [ ] Use admin panel to assign prize manually
- [ ] Use admin panel to preview auto-assignment
- [ ] Use admin panel to confirm auto-assignment

## 20.5 Security

- [x] Replay same signed request
- [x] Try invalid signature
- [x] Try expired timestamp
- [x] Try duplicate score submit

## 20.6 Admin panel

- [ ] Login as admin
- [ ] Check all resources open correctly
- [ ] Check non-admin cannot access panel

---

# 21. Performance and cleanup

## 21.1 Query review

- [ ] Review leaderboard queries
- [ ] Review shop queries
- [ ] Review profile queries
- [ ] Eliminate N+1 issues

## 21.2 Caching review

- [ ] Decide if leaderboard caching is needed
- [ ] Cache only where safe
- [ ] Avoid stale critical state

## 21.3 Refactor review

- [ ] Remove duplicated logic
- [ ] Keep services coherent
- [ ] Keep method names explicit

## 21.4 Security review

- [ ] Review middleware coverage
- [ ] Review auth boundaries
- [ ] Review admin restrictions
- [ ] Review logs for sensitive data leaks

---

# 22. API Documentation (Swagger)

## 22.1 Swagger integration

- [x] Install and configure Swagger/OpenAPI package
- [x] Configure documentation route
- [x] Ensure generation command works

## 22.2 API documentation coverage

- [x] Document auth endpoints
- [x] Document profile endpoints
- [x] Document shop endpoints
- [x] Document game/session endpoints
- [x] Document leaderboard endpoints
- [x] Document prizes endpoints

## 22.3 Reusable schemas

- [x] Define response envelope schemas
- [x] Define common resource schemas (user, skin, prize, leaderboard)
- [x] Define error response schemas

## 22.4 Security documentation

- [x] Document Sanctum bearer auth
- [x] Document request-signature headers
- [x] Apply security schemes to relevant endpoints

## 22.5 Examples

- [x] Add request examples for key endpoints
- [x] Add response examples

## 22.6 README

- [x] Project setup
- [x] Environment variables
- [x] Migration and seeding commands
- [x] Queue / Redis notes
- [x] Admin access notes
- [ ] Running tests

## 22.7 API documentation

- [x] List endpoints
- [x] Describe auth flow
- [x] Show request examples
- [x] Show response examples
- [x] Show signature header requirements

## 22.8 Developer notes

- [ ] Document ranking logic
- [ ] Document prize assignment logic
- [ ] Document anti-abuse limitations
- [ ] Document why direct requests cannot be fully eliminated

---

# 23. Final acceptance criteria

Project is considered ready when all of the following are true:

- [ ] Laravel API is working
- [ ] Auth via Sanctum is working
- [ ] Profile endpoints are working
- [ ] Shop endpoints are working
- [ ] Game session issuing is working
- [ ] Score submission is working
- [ ] Leaderboard top 15 is working
- [ ] Public leaderboard hides full emails
- [ ] Prize system is working
- [ ] Admin panel is working
- [ ] Rate limiting is configured
- [ ] Request signature validation is working
- [ ] Replay protection is working
- [ ] Core tests are passing
- [ ] README and API docs are written

---

# 24. Recommended commit sequence

- [ ] `chore: bootstrap laravel project and install core packages`
- [ ] `feat: add core game database schema and models`
- [ ] `feat: implement sanctum authentication`
- [ ] `feat: add profile module`
- [ ] `feat: add shop and skin purchase flow`
- [ ] `feat: add game sessions and score submission`
- [ ] `feat: add leaderboard module`
- [ ] `feat: add prize domain and user prize endpoints`
- [ ] `feat: add filament admin panel resources`
- [ ] `feat: implement request signature and replay protection`
- [ ] `test: add feature and unit tests for core flows`
- [ ] `docs: add readme and api documentation`

---

# 25. Nice-to-have after MVP

Do not block MVP on these tasks.

- [ ] Seasonal leaderboard snapshots
- [ ] Username/nickname support
- [ ] Achievement system
- [ ] Daily rewards
- [ ] Push notifications
- [ ] Real-time leaderboard updates
- [ ] Anti-fraud moderation dashboard
- [ ] Device fingerprint hardening
