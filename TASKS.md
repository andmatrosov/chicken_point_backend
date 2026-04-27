# TASKS.md

## Current state

This file tracks the actual current system state and the remaining work.
It is not a historical bootstrap checklist anymore.

## Implemented

### API and auth

- Bearer-only API authentication via Laravel Sanctum
- Device-aware register/login contract:
  - `device_id`
  - `platform`
  - `app_version`
- Auth and profile payloads expose persisted `country_code` and `country_name`
- Token lifecycle endpoints:
  - `POST /api/auth/logout`
  - `POST /api/auth/logout-all-devices`
- `GET /api/me`
- Public GeoIP endpoint:
  - `GET /api/country`
- Public MVP settings endpoints:
  - `GET /api/mvp-settings/main`
  - `GET /api/mvp-settings/brazil`
- OpenAPI / Swagger contract version: `1.5.0`
- Auth email handling trims and lowercases login/register emails, uses stricter validation, and rejects case-insensitive duplicates

### Core gameplay

- Profile endpoints:
  - `GET /api/profile`
  - `GET /api/profile/skins`
  - `POST /api/profile/active-skin`
  - `GET /api/profile/rank`
- Shop endpoints:
  - `GET /api/game/shop`
  - `POST /api/game/shop/buy-skin`
- Game endpoints:
  - `GET /api/game/leaderboard`
  - `POST /api/game/session/start`
  - `POST /api/game/session/close`
  - `POST /api/game/submit-score`
- Public shop listing remains intentionally unauthenticated and supports optional Sanctum enrichment for ownership and active-skin flags
- Public leaderboard remains intentionally unauthenticated and supports optional Sanctum enrichment for current-user rank/score
- Starting a new game session automatically cancels any previous active session for the same user
- Active game sessions remain valid until they are submitted, explicitly closed, or replaced by a new session start
- `submit-score` currently accepts:
  - `session_token`
  - `score`
  - `coins_collected`
  - optional technical `metadata`
- `submit-score` persists accepted `coins_collected` in `game_scores` and applies it to the authenticated user's balance inside the successful submission flow
- `submit-score` runs an adaptive anti-cheat model using only server-side session time
- suspicious submissions add accumulated suspicion points instead of immediately flagging on a single soft velocity anomaly
- leaderboard entries exclude users flagged for suspicious game results
- historical suspicious backfill is supported through `game:recalculate-suspicious-results`
- suspicious backfill reset is supported through `game:reset-suspicious-results`
- Prize endpoint:
  - `GET /api/prizes/my`

### Security model

- HMAC/request-signature middleware removed from the API contract
- Protected routes rely on:
  - `auth:sanctum`
  - route-specific rate limits
  - server-side validation
  - ownership checks
  - server-issued game sessions
- Score submission is hardened by:
  - session ownership
  - active/submitted state checks
  - one-time submit rule
  - score range validation
  - adaptive suspicious-score detection based on server session time
  - accumulated suspicion points with permanent flagging only after the configured threshold
  - collected-coins range validation
  - metadata consistency checks when session metadata exists
- Suspicious session and score events are logged in a structured way
- historical suspicious recalculation is idempotent through persisted `user_suspicious_events`

### Admin and platform

- Filament admin panel is installed
- Filament includes an MVP settings resource for `main` and `brazil`
- Core game domain entities, relationships, and migrations are in place
- GeoIP country detection uses a local MaxMind database only
- Public request-country checks reuse the same local GeoIP lookup path
- Swagger / OpenAPI documents the public API
- MVP settings defaults are auto-created for `main` and `brazil`
- Production boot enforces safe deployment basics:
  - `APP_DEBUG=false`
  - HTTPS `APP_URL`
  - required secrets present

### Tests

- Feature coverage exists for:
  - auth
  - profile
  - shop
  - game session and score submission
  - API protection and rate limiting
- Unit coverage exists for key services and validation rules
- Test helpers now reflect the bearer-only API contract

## Completed migration stages

- Stage 1: removed the legacy HMAC request-signature layer
- Stage 2: normalized Sanctum bearer auth and token lifecycle
- Stage 3: hardened game session flow and score submission
- Stage 4: aligned the test layer with the bearer-only contract
- Stage 5: aligned OpenAPI and project documentation with the current API

## Remaining work

### High priority

- Decide whether Sanctum token expiry should remain time-based for all environments or become environment-specific
- Add more focused tests around token expiry behavior if the policy changes
- Review whether authenticated `GET` test helpers should be standardized further for readability

### Product / domain

- Continue prize/admin workflow improvements where needed
- Add any remaining admin-side operational tooling only if product requirements demand it

### Optional cleanup

- Introduce stricter API structure/versioning only as a separate future refactor
- Add DTO/resource-scoped refactors only after current API behavior is stable
- Expand docs only when runtime behavior changes; do not drift markdown/OpenAPI away from the code

## Out of scope for the current system

- HMAC or request-signature auth
- nonce-based replay protection at the HTTP layer
- cookie/session-based auth for API clients
- OAuth or refresh-token flows
- API versioning refactor

## Working rule

When API behavior changes, update all of the following together:

- routes and runtime code
- OpenAPI definitions
- README
- AGENTS.md
- tests
