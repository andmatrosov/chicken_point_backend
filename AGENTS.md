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
- Swagger / OpenAPI 1.3.0 via `l5-swagger`

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

### Game

- `GET /api/game/leaderboard`
- `POST /api/game/session/start`
- `POST /api/game/submit-score`

`GET /api/game/leaderboard` is intentionally public. If a valid Sanctum bearer token is present, the response may also include `current_user_rank` and `current_user_score`.

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
- session TTL enforcement
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
3. the server validates active / expired / submitted state
4. the server validates score range
5. the server validates `coins_collected` range
6. the server validates technical metadata consistency when session metadata exists
7. a `game_scores` row is created
8. the session is marked as submitted
9. `best_score` is updated only if the new score is higher
10. the authenticated user's `coins` balance is incremented by accepted `coins_collected`

The server remains the source of truth.

## Current config knobs

Notable `config/game.php` values:

- leaderboard size
- game session TTL
- session token length
- optional active-session invalidation / limit
- password minimum length
- auto-activate first purchased skin
- prize stock mode
- score min / max
- max coins accepted per run
- duration min / max
- route rate limits

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

## Current seeding and local bootstrap

Default seeding creates exactly one deterministic non-admin user:

- email: `test@example.com`
- password: `password`
- `is_admin = false`

No admin user, demo skins, demo prizes, or demo leaderboard dataset are created by default.

If local admin access is needed, promote a user manually.

## Current docs state

API docs are maintained in:

- `app/OpenApi`
- `README.md`

OpenAPI version is currently `1.3.0`.

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
