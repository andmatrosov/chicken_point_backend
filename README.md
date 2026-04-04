# README.md

## Project

Laravel backend for a mobile game with:

- Sanctum API authentication
- profile and owned skins
- best-score leaderboard
- shop and skin purchases
- server-issued game sessions
- prize assignments
- Filament admin panel
- signed-request and nonce replay protection

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
```

If you use PostgreSQL, replace the database settings accordingly.

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

## API overview

### Auth

- `POST /api/auth/register`
- `POST /api/auth/login`
- `POST /api/auth/logout`
- `GET /api/me`

### Profile

- `GET /api/profile`
- `GET /api/profile/skins`
- `POST /api/profile/active-skin`
- `GET /api/profile/rank`

### Game

- `GET /api/game/leaderboard`
- `POST /api/game/session/start`
- `POST /api/game/submit-score`

### Shop

- `GET /api/game/shop`
- `POST /api/game/shop/buy-skin`

### Prizes

- `GET /api/prizes/my`

## Auth usage

This project uses Laravel Sanctum bearer tokens for authenticated API access.

Login or register first, then send the token as:

```http
Authorization: Bearer <token>
```

## Leaderboard behavior

`GET /api/game/leaderboard` is public.

- guest requests receive only the public leaderboard entries
- authenticated requests may also receive `current_user_rank` and `current_user_score`
- public leaderboard entries never expose full email addresses

Guest example:

```bash
curl http://localhost:8000/api/game/leaderboard
```

If you include a valid Sanctum bearer token on the same route, the response may also include the current authenticated user's rank and score.

## Example requests

### Register

```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player1@example.com",
    "password": "secret12345",
    "password_confirmation": "secret12345"
  }'
```

### Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "player1@example.com",
    "password": "secret12345"
  }'
```

### Get profile

```bash
curl http://localhost:8000/api/profile \
  -H "Authorization: Bearer <token>"
```

### Start game session

```bash
curl -X POST http://localhost:8000/api/game/session/start \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{}'
```

### Submit score

`score` submission accepts only session metadata such as duration, app version, and device ID. Currency rewards are not accepted from the client and must be calculated on the server if they are introduced later.

```bash
curl -X POST http://localhost:8000/api/game/submit-score \
  -H "Authorization: Bearer <token>" \
  -H "X-Timestamp: <unix-timestamp>" \
  -H "X-Nonce: <unique-nonce>" \
  -H "X-Signature: <request-signature>" \
  -H "Content-Type: application/json" \
  -d '{
    "session_token": "SESSION_TOKEN_HERE",
    "score": 2450,
    "metadata": {
      "duration": 120,
      "app_version": "1.0.0",
      "device_id": "ios-device-1"
    }
  }'
```

### Buy skin

```bash
curl -X POST http://localhost:8000/api/game/shop/buy-skin \
  -H "Authorization: Bearer <token>" \
  -H "X-Timestamp: <unix-timestamp>" \
  -H "X-Nonce: <unique-nonce>" \
  -H "X-Signature: <request-signature>" \
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
  "message": "Validation failed",
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

- authenticated mutation routes use Sanctum auth
- signed mutation routes require `X-Timestamp`, `X-Nonce`, and `X-Signature`
- nonce replay protection is enforced
- score submission requires a valid server-issued session token
- leaderboard output masks other users' emails
- the server remains the source of truth for scores, balances, owned items, and prizes

### Signed mutation routes

Currently enforced signed routes:

- `POST /api/profile/active-skin`
- `POST /api/game/session/start`
- `POST /api/game/submit-score`
- `POST /api/game/shop/buy-skin`

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

## Swagger / OpenAPI

Swagger / OpenAPI is integrated using `darkaonline/l5-swagger`.

- UI: `/api/documentation`
- Raw OpenAPI JSON: `/api/documentation/docs`

### Generate docs

```bash
php artisan l5-swagger:generate
```

### Enable the docs routes when needed

```dotenv
L5_SWAGGER_ENABLED=true
```

### Signed mutation headers

Signed mutation endpoints also require:

- `X-Timestamp`
- `X-Nonce`
- `X-Signature`

## Production notes

Before production deploy:

- set `APP_ENV=production`
- set `APP_DEBUG=false`
- use HTTPS only
- use secure database and Redis credentials
- provision admin accounts explicitly
- configure queue worker supervision
- verify rate limits
- verify request-signature middleware on intended mutation routes
- keep the mounted GeoLite2 Country database file up to date
