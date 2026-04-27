# Mobile Game Backend

## Project

Laravel backend for a mobile game with:

- Sanctum API authentication
- frontend-specific MVP link settings
- profile and owned skins
- best-score leaderboard
- shop and skin purchases
- server-issued game sessions
- prize assignments
- Filament admin panel

## Requirements

- PHP 8.3+
- Composer
- MySQL 8+ or PostgreSQL
- Redis
- Git
- Node.js and npm only if frontend assets are needed for admin theme build

## Installation

```bash
git clone <repository-url> mobile-game-backend
cd mobile-game-backend
composer install
cp .env.example .env
php artisan key:generate
```

Configure the main values in `.env`.

Example:

```dotenv
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

TRUSTED_PROXIES=
TRUSTED_PROXY_HEADERS=
```

If you use PostgreSQL, replace the database settings accordingly.

If the app runs behind a reverse proxy or load balancer, set `TRUSTED_PROXIES` to the explicit proxy IPs or CIDR ranges that are allowed to supply forwarded headers. Leave it empty when the app is reached directly. `TRUSTED_PROXY_HEADERS` may be used only when your infrastructure requires a non-default forwarded-header mode.

## Database setup

Run migrations:

```bash
php artisan migrate
```

For upgrades on an existing database, verify these assumptions before applying new migrations:

- `game_scores.session_token` must not contain duplicates before the unique index migration is applied
- the legacy `user_prizes (user_id, prize_id)` index should still exist before the replacement migration runs

Recommended preflight:

- check for duplicate `session_token` values in `game_scores` and clean them up first
- inspect existing `user_prizes` indexes so the replacement migration does not fail on drifted schemas
- run the migration on a staging copy first if production schema history is not clean

Seed the local bootstrap user:

```bash
php artisan db:seed
```

This creates exactly one deterministic non-admin local user:

- email: `test@example.com`
- password: `password`
- `is_admin`: `false`

No admin user, demo leaderboard data, skins, or prizes are created by default.

The baseline `mvp_settings` records for `main` and `brazil` are created automatically by migrations.

`php artisan migrate:fresh --seed` recreates the same single bootstrap user.

## Local development

Start the app:

```bash
php artisan serve
```

Run the queue worker:

```bash
php artisan queue:work
```

If a scheduler is needed later:

```bash
php artisan schedule:work
```

## Admin access

Only users with `is_admin = true` can access Filament.

No admin user is seeded by default. For local setup, promote an existing user explicitly:

```bash
php artisan tinker
```

```php
$user = \App\Models\User::query()->where('email', 'test@example.com')->firstOrFail();
$user->update(['is_admin' => true]);
```

In the Filament user view, admins can inspect the user's current leaderboard rank, registration IP, and detected country name when those values are available.
Admins can also manually enable or clear the suspicious-results flag on a user.

## API overview

Documented API version: `1.5.0`

### Auth

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `POST /api/auth/logout-all-devices`
- `GET /api/me`

### Profile

- `GET /api/profile`
- `GET /api/profile/skins`
- `POST /api/profile/active-skin`
- `GET /api/profile/rank`

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

### Shop

- `GET /api/game/shop`
- `POST /api/game/shop/buy-skin`

### Prizes

- `GET /api/prizes/my`

## Auth usage

This project uses Laravel Sanctum personal access tokens as the only authentication mechanism for API clients.

Login or register first, then send the token as:

```http
Authorization: Bearer <token>
```

The login and registration contract also requires explicit client metadata:

- `device_id`
- `platform`
- `app_version`

Auth email handling rules:

- registration trims and lowercases `email`, validates it with a strict non-DNS email format rule, and stores the normalized lowercase value
- login trims and lowercases `email` before authentication and validates it with the same strict non-DNS email format rule
- `Test@Mail.com` and `test@mail.com` are treated as the same user identity

The bearer token lifetime is controlled by `SANCTUM_TOKEN_EXPIRATION_MINUTES`. The default in this project is `43200` minutes (30 days).

Successful responses from `POST /api/auth/register`, `POST /api/auth/login`, `GET /api/me`, and `GET /api/profile` include:

- `country_code`
- `country_name`

These values are stored on the user from the detected registration country. Example:

```json
{
  "country_name": "Georgia",
  "country_code": "GE"
}
```

Token lifecycle endpoints:

- `POST /api/auth/logout` revokes only the current bearer token
- `POST /api/auth/logout-all-devices` revokes all bearer tokens for the authenticated user
- authenticated token lifecycle and read endpoints are rate limited per authenticated user

## Leaderboard behavior

`GET /api/game/leaderboard` is public.

- guest requests receive only the public leaderboard entries
- authenticated requests may also receive `current_user_rank` and `current_user_score`
- users with the suspicious-results flag are excluded from leaderboard entries
- per-IP throttling applies
- public leaderboard entries never expose full email addresses

Guest example:

```bash
curl http://localhost:8000/api/game/leaderboard
```

If you include a valid Sanctum bearer token on the same route, the response may also include the current authenticated user's rank and score.

## Shop behavior

`GET /api/game/shop` is public.

- guest requests receive only active skins
- guest requests receive `is_owned=false` and `is_active_for_user=false` for every item
- authenticated requests may include personalized `is_owned` and `is_active_for_user` flags when a valid Sanctum bearer token is present

Guest example:

```bash
curl http://localhost:8000/api/game/shop
```

Authenticated example:

```bash
curl http://localhost:8000/api/game/shop \
  -H "Authorization: Bearer <token>"
```

## MVP settings behavior

`GET /api/mvp-settings/main` and `GET /api/mvp-settings/brazil` are public.

- both endpoints return the standard API envelope
- both endpoints are rate limited per IP
- `data.version` identifies the frontend version
- `data.mvp_link` is nullable
- `data.is_active` tells the client whether the link should currently be used

## Example requests

### Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player1@example.com",
    "password": "secret12345",
    "password_confirmation": "secret12345",
    "device_id": "ios-device-1",
    "platform": "ios",
    "app_version": "1.0.0"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player1@example.com",
    "password": "secret12345",
    "device_id": "ios-device-1",
    "platform": "ios",
    "app_version": "1.0.0"
  }'
```

### Logout current token

```bash
curl -X POST http://localhost:8000/api/auth/logout \
  -H "Authorization: Bearer <token>"
```

### Logout all devices

```bash
curl -X POST http://localhost:8000/api/auth/logout-all-devices \
  -H "Authorization: Bearer <token>"
```

### Get profile

```bash
curl http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>"
```

### Get MVP settings for main

```bash
curl http://localhost:8000/api/mvp-settings/main
```

### Get MVP settings for brazil

```bash
curl http://localhost:8000/api/mvp-settings/brazil
```

### Check request country

`GET /api/country` is public, rate limited per IP, and resolves the country for the current request IP using the local GeoIP database.

```bash
curl http://localhost:8000/api/country
```

### Start game session

`session/start` accepts optional metadata. If `device_id`, `platform`, or `app_version` are stored at session start, the same values must be provided on score submission.

Starting a new session automatically cancels any previous active session for the same authenticated user.

```bash
curl -X POST http://localhost:8000/api/game/session/start \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "metadata": {
      "device_id": "ios-device-1",
      "platform": "ios",
      "app_version": "1.0.0"
    }
  }'
```

### Close game session

`session/close` closes an active session explicitly. The session must belong to the authenticated user and still be active.

```bash
curl -X POST http://localhost:8000/api/game/session/close \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "SESSION_TOKEN_HERE"
  }'
```

### Submit score

`submit-score` requires a valid server-issued session token that:

- exists
- belongs to the authenticated user
- is still active
- has not already been submitted

The request accepts:

- top-level `score`
- top-level `coins_collected`
- technical `metadata` only for `duration`, `device_id`, `platform`, and `app_version`

`coins_collected` must not be placed inside `metadata`. The server validates the submitted coin value against configured limits, persists the accepted value in `game_scores`, and then applies it to the authenticated user's balance. If session metadata was recorded at session start, the submitted metadata values must match.

The server also runs an adaptive anti-cheat check using only server-side session time (`issued_at` to submission time). `metadata.duration` is not trusted for anti-cheat decisions.

Suspicious submissions are still saved, but they produce suspicion points:

- hard suspicious: score meets or exceeds the adaptive max score for the measured server-side session duration, adds `3` points
- soft suspicious: score is at least the configured minimum and also exceeds the configured score velocity threshold, adds `1` point

Users are permanently flagged only after reaching the configured points threshold. Flagged users are excluded from leaderboard participation and leaderboard-based prize flows. This is controlled by:

- `GAME_ANTICHEAT_MODE`: `off`, `log`, or `flag` (`flag` by default)
- `GAME_SOFT_SCORE_VELOCITY_THRESHOLD`: soft suspicious score-per-second threshold (`4.0` by default)
- `GAME_SOFT_SCORE_MINIMUM`: minimum score before the soft suspicious rule applies (`50` by default)
- `GAME_SUSPICION_POINTS_TO_FLAG`: points required to set the permanent suspicious-results flag (`3` by default)

For backward compatibility, legacy env vars `GAME_SCORE_VELOCITY_MODE` and `GAME_MAX_SCORE_PER_SECOND` are still accepted as fallbacks.

### Historical suspicious-results recalculation

To preview historical recalculation without changing data:

```bash
php artisan game:recalculate-suspicious-results --dry-run
```

To apply the recalculation:

```bash
php artisan game:recalculate-suspicious-results
```

Useful options:

- `--user_id=` to recalculate only one user
- `--from_id=` to start from a specific `game_scores.id`
- `--chunk=` to control chunk size

Historical recalculation uses only server timestamps:

- `game_sessions.issued_at`
- `game_scores.created_at`

To avoid duplicating points on repeated runs, each applied suspicious historical score creates a `user_suspicious_events` row keyed by `game_score_id`.

To reset recalculated suspicious data:

```bash
php artisan game:reset-suspicious-results
```

Dry-run reset preview:

```bash
php artisan game:reset-suspicious-results --dry-run
```

Reset clears:

- `users.suspicious_game_result_points`
- `users.has_suspicious_game_results`
- `users.suspicious_game_results_flagged_at`
- `users.suspicious_game_results_reason`
- `user_suspicious_events`

```bash
curl -X POST http://localhost:8000/api/game/submit-score \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "SESSION_TOKEN_HERE",
    "score": 2450,
    "coins_collected": 17,
    "metadata": {
      "duration": 120,
      "app_version": "1.0.0",
      "device_id": "ios-device-1",
      "platform": "ios"
    }
  }'
```

### Buy skin

`POST /api/game/shop/buy-skin` requires a valid Sanctum bearer token.

```bash
curl -X POST http://localhost:8000/api/game/shop/buy-skin \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "skin_id": 2
  }'
```

## Response format

### Success

```json
{
  "success": true,
  "data": {},
  "meta": {}
}
```

### Validation error

```json
{
  "success": false,
  "message": "Validation error.",
  "errors": {
    "field": ["The field is required."]
  }
}
```

### Business error

```json
{
  "success": false,
  "message": "Not enough coins"
}
```

## Security and runtime notes

Important points:

- authenticated API routes use Sanctum bearer auth only
- login and registration require device/application context
- logout revokes the current bearer token only
- logout-all-devices revokes all bearer tokens for the authenticated user
- score submission requires a valid server-issued session token and enforces one-time use
- score submission accepts top-level `coins_collected`, but the server still validates and caps accepted values before updating balance
- session metadata consistency is enforced when `device_id`, `platform`, or `app_version` were stored at session start
- leaderboard output masks other users' emails
- the server remains the source of truth for scores, balances, owned items, and prizes
- production boot enforces basic deployment safety checks, including `APP_DEBUG=false` and HTTPS `APP_URL`

## Swagger / OpenAPI

Swagger / OpenAPI is generated from PHP attributes in `app/OpenApi`.

Current documented API version: `1.5.0`

- UI: `/api/documentation`
- Raw OpenAPI JSON: `/api/documentation/docs`

Generate docs with:

```bash
php artisan l5-swagger:generate
```

Enable the docs routes when needed:

```dotenv
L5_SWAGGER_ENABLED=true
```

### GeoIP

Local GeoIP country detection uses a local MaxMind GeoLite2 Country database only.

- local fallback path: `storage/app/geoip/GeoLite2-Country.mmdb`
- production env override: `GEOIP_COUNTRY_DATABASE_PATH`
- recommended production path: `/data/geoip/GeoLite2-Country.mmdb`

If the GeoIP database file is missing, unreadable, or the IP is private or invalid, lookup returns `null` and the request continues.

Recommended production env value:

```dotenv
GEOIP_COUNTRY_DATABASE_PATH=/data/geoip/GeoLite2-Country.mmdb
```

Example Docker bind mount:

```bash
docker run \
  -e GEOIP_COUNTRY_DATABASE_PATH=/data/geoip/GeoLite2-Country.mmdb \
  -v /data/geoip/GeoLite2-Country.mmdb:/data/geoip/GeoLite2-Country.mmdb:ro \
  your-app-image
```

## Testing and useful commands

### Run tests

```bash
php artisan test
```

or:

```bash
vendor/bin/pest
```

### Useful commands

```bash
php artisan optimize:clear
php artisan migrate:fresh --seed
php artisan queue:work
php artisan tinker
```

### GeoIP quick check in Tinker

```php
app(\App\Services\GeoIpService::class)->detectCountry('8.8.8.8');
app(\App\Services\GeoIpService::class)->detectCountryCode('1.1.1.1');
```


## Production notes

Before production deploy:

- set `APP_ENV=production`
- set `APP_DEBUG=false`
- use HTTPS only
- use secure database and Redis credentials
- provision admin accounts explicitly
- configure queue worker supervision
- verify rate limits
- keep the mounted GeoLite2 Country database file up to date
