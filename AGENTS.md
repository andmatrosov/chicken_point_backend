# AGENTS.md

## Project

Laravel backend for a mobile game.

This file describes the current implemented project state and the working rules for future changes. It is not a bootstrap wishlist anymore.

## Current stack

- Laravel
- PHP 8.3+
- MySQL 8+ or PostgreSQL
- Redis for cache and queues
- Laravel Sanctum personal access tokens
- Filament admin panel
- Local MaxMind GeoIP lookup
- PHPUnit feature and unit tests
- Swagger / OpenAPI 1.5.0 via `l5-swagger`

## Current API surface

### Auth

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/logout-all-devices`
- `GET /api/me`

Register and login require:

- `email`
- `password`
- `device_id`
- `platform`
- `app_version`

Auth email rules:

- registration trims and lowercases `email` before validation and persistence
- registration validates `email` with a strict non-DNS email format rule
- login trims and lowercases `email` before authentication
- login validates `email` with the same strict non-DNS email format rule
- `Test@Mail.com` and `test@mail.com` must resolve to the same account identity

### Profile

- `GET /api/profile`
- `GET /api/profile/skins`
- `POST /api/profile/active-skin`
- `GET /api/profile/rank`

`POST /api/auth/register`, `POST /api/auth/login`, `GET /api/me`, and `GET /api/profile` include:

- `country_name`
- `country_code`

### GeoIP

- `GET /api/country`

### MVP Settings

- `GET /api/mvp-settings/main`
- `GET /api/mvp-settings/brazil`

### Game

- `GET /api/game/leaderboard`
- `POST /api/game/session/start`
- `POST /api/game/session/close`
- `POST /api/game/submit-score`

`GET /api/game/leaderboard` is intentionally public. If a valid Sanctum bearer token is present, the response may also include `current_user_rank` and `current_user_score`.

Users with the suspicious-results flag are excluded from leaderboard entries and leaderboard-based prize flows.

`POST /api/game/submit-score` currently accepts:

```json
{
  "session_token": "SESSION_TOKEN_HERE",
  "score": 2450,
  "coins_collected": 17,
  "metadata": {
    "duration": 120,
    "app_version": "1.0.0",
    "device_id": "ios-device-1",
    "platform": "ios"
  }
}
```

Rules:

- `coins_collected` is a top-level field
- `coins_collected` must not be placed inside `metadata`
- `metadata` is limited to technical/session context only:
  - `duration`
  - `app_version`
  - `device_id`
  - `platform`

### Shop

- `GET /api/game/shop`
- `POST /api/game/shop/buy-skin`

`GET /api/game/shop` is intentionally public. Guest responses return only active skins with `is_owned = false` and `is_active_for_user = false` for every item. If a valid Sanctum bearer token is present, the same route may also include personalized ownership and active-skin flags.

### Prizes

- `GET /api/prizes/my`

## Current security model

The HTTP-layer request-signature / HMAC flow is not part of the current system anymore.

The active protection model is:

- Sanctum bearer auth for protected routes
- route-specific rate limiting
- policy / authorization checks
- server-issued game sessions for score submission
- server-side validation only
- ownership checks
- one-time session submission rule
- explicit active-session lifecycle via start / close / submit
- metadata consistency enforcement when session metadata was stored at session start
- structured suspicious-event logging

Important:

- do not reintroduce request-signature middleware unless there is an explicit product decision
- do not use `user_id` from request payloads as an identity source
- do not trust client-calculated balances, rank, ownership, or rewards

## Current score submission behavior

On successful `submit-score`:

1. the server validates the session token
2. the server validates session ownership
3. the server validates active / submitted state
4. the server validates score range
5. the server detects suspicious submissions using server-side anti-cheat rules based on actual elapsed session time
6. the server validates `coins_collected` range
7. the server validates technical metadata consistency when session metadata exists
8. a `game_scores` row is created with `score` and accepted `coins_collected`
9. the session is marked as submitted
10. `best_score` is updated only if the new score is higher
11. the authenticated user's `coins` balance is incremented by accepted `coins_collected`
12. suspicious submissions are logged and can add suspicion points to the user
13. the persistent suspicious-results flag is set only after the configured points threshold is reached
14. suspicious submissions can accumulate multiple signals at once, including score-based signals and server/client duration mismatch
15. duration mismatch is currently treated as a diagnostic timing signal and does not contribute points by default
16. unreliable server duration suppresses adaptive score-limit and score-velocity checks and is stored as a timing diagnostic only

The server remains the source of truth.

## Current config knobs

Notable `config/game.php` values:

- leaderboard size
- session token length
- route rate limits including session start / close / submit
- password minimum length
- auto-activate first purchased skin
- prize stock mode
- score min / max
- anti-cheat mode
- soft suspicious score velocity threshold
- soft suspicious minimum score
- suspicion points threshold
- duration mismatch enable flag
- duration mismatch grace seconds / grace percent / points
- minimum reliable server duration
- minimum client duration and score thresholds for unreliable-timing detection
- adaptive score limits by elapsed session duration
- max coins accepted per run
- duration min / max
- route rate limits
- MVP settings public endpoint rate limit

## Current anti-cheat maintenance tooling

Available console commands:

- `php artisan game:recalculate-suspicious-results`
- `php artisan game:recalculate-suspicious-results --dry-run`
- `php artisan game:reset-suspicious-results`
- `php artisan game:reset-suspicious-results --dry-run`

Historical recalculation uses:

- `game_sessions.issued_at`
- `game_scores.created_at`

It must not use `metadata.duration`.

Runtime submit anti-cheat uses the captured submit timestamp and persists it into `game_sessions.submitted_at`. Historical timing diagnostics intentionally use `game_scores.created_at` as the submit-time proxy.
If a session is classified with unreliable server duration, duration-based cheat detection is skipped and only timing signals are persisted.

Applied historical suspicious events are stored in `user_suspicious_events` keyed by `game_score_id` so recalculation stays idempotent.

`user_suspicious_events` stores the aggregate `reason` plus structured `signals` and `context` JSON for admin inspection.

Sanctum token expiration is configured by `SANCTUM_TOKEN_EXPIRATION_MINUTES`.

## Current admin state

Filament is installed.

Current resources / pages:

- `Users`
- `Skins`
- `Prizes`
- `UserPrizes`
- `GameScores`
- `GameSessions`
- `AdminActionLogs`
- `Leaderboard` page

Admin access is controlled by `is_admin` plus the `access-admin-panel` gate.

Admins can manually enable or clear the suspicious-results flag from the user edit page and can reset accumulated suspicion points without clearing the permanent flag.
The user view page also includes an `Игровые результаты` relation tab with suspicious session diagnostics.

## Current seeding and local bootstrap

Default seeding creates exactly one deterministic non-admin user:

- email: `test@example.com`
- password: `password`
- `is_admin = false`

No admin user, demo skins, demo prizes, or demo leaderboard dataset are created by default.

Baseline `mvp_settings` records for `main` and `brazil` are created automatically by migrations.

If local admin access is needed, promote a user manually.

## Current docs state

API docs are maintained in:

- `app/OpenApi`
- `README.md`

OpenAPI version is currently `1.5.0`.

When API behavior changes, update all of these together:

- routes
- requests / validation
- actions / services
- resources / responses
- tests
- OpenAPI attributes
- `README.md`
- `TASKS.md` and this file when the system state changed materially

Regenerate Swagger docs with:

```bash
php artisan l5-swagger:generate
```

## Working rules for future changes

1. Keep controllers thin.
2. Keep business logic in actions and services.
3. Preserve the public leaderboard as a no-auth route.
4. Preserve bearer-only API auth unless explicitly changed.
5. Do not move `coins_collected` into `metadata`.
6. Do not document behavior that does not exist in runtime code.
7. Do not add broad versioning refactors unless they are a separate task.
8. Treat markdown docs and OpenAPI as part of the production contract.
