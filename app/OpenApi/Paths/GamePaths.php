<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class GamePaths
{
    #[OA\Get(
        path: '/api/game/shop',
        operationId: 'getShopSkins',
        tags: ['Shop'],
        summary: 'Get the active shop skin list',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/SkinCollectionResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
        ],
    )]
    public function shopIndex(): void
    {
    }

    #[OA\Post(
        path: '/api/game/shop/buy-skin',
        operationId: 'buySkin',
        tags: ['Shop'],
        summary: 'Buy a skin',
        security: [['sanctumBearer' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/BuySkinRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableApiResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function buySkin(): void
    {
    }

    #[OA\Post(
        path: '/api/game/session/start',
        operationId: 'startGameSession',
        tags: ['Game'],
        summary: 'Start a server-issued game session',
        description: 'Requires Sanctum bearer auth. Issues a server-side gameplay session tied to the authenticated user. Optional device metadata may be stored with the session and, when present, must match on the later one-time score submission.',
        security: [['sanctumBearer' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StartGameSessionRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/SessionStartResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableApiResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function startSession(): void
    {
    }

    #[OA\Post(
        path: '/api/game/submit-score',
        operationId: 'submitScore',
        tags: ['Game'],
        summary: 'Submit a score and collected coins for an active game session',
        description: 'Requires Sanctum bearer auth and a valid server-issued session token. The session must belong to the authenticated user, remain active, not be expired, and not have been used before. The request accepts top-level score and coins_collected values. Collected coins are validated server-side before they are applied to the user balance. If technical session metadata was recorded at session start, the submitted metadata must match.',
        security: [['sanctumBearer' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/SubmitScoreRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableApiResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function submitScore(): void
    {
    }

    #[OA\Get(
        path: '/api/game/leaderboard',
        operationId: 'getLeaderboard',
        tags: ['Game'],
        summary: 'Get public leaderboard entries',
        description: 'Public leaderboard endpoint. This route MUST remain accessible without authentication. Authenticated requests (via Sanctum bearer token) may include current_user_rank and current_user_score. Per-IP throttling applies. Do not add auth requirements to this endpoint.',
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/LeaderboardResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function leaderboard(): void
    {
    }
}
