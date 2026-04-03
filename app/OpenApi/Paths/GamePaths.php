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
        description: 'Requires both Sanctum bearer auth and request-signature headers.',
        security: [['sanctumBearer' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/XTimestampHeader'),
            new OA\Parameter(ref: '#/components/parameters/XNonceHeader'),
            new OA\Parameter(ref: '#/components/parameters/XSignatureHeader'),
        ],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/BuySkinRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 400, ref: '#/components/responses/MissingSignatureHeadersResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/InvalidRequestSignatureResponse'),
            new OA\Response(response: 409, ref: '#/components/responses/NonceReplayResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/BusinessErrorResponse'),
            new OA\Response(response: 503, ref: '#/components/responses/RequestVerificationUnavailableResponse'),
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
        description: 'Requires both Sanctum bearer auth and request-signature headers.',
        security: [['sanctumBearer' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/XTimestampHeader'),
            new OA\Parameter(ref: '#/components/parameters/XNonceHeader'),
            new OA\Parameter(ref: '#/components/parameters/XSignatureHeader'),
        ],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/StartGameSessionRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/SessionStartResponse'),
            new OA\Response(response: 400, ref: '#/components/responses/MissingSignatureHeadersResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/InvalidRequestSignatureResponse'),
            new OA\Response(response: 409, ref: '#/components/responses/NonceReplayResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationErrorResponse'),
            new OA\Response(response: 503, ref: '#/components/responses/RequestVerificationUnavailableResponse'),
        ],
    )]
    public function startSession(): void
    {
    }

    #[OA\Post(
        path: '/api/game/submit-score',
        operationId: 'submitScore',
        tags: ['Game'],
        summary: 'Submit a score for an active game session',
        description: 'Requires both Sanctum bearer auth and request-signature headers.',
        security: [['sanctumBearer' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/XTimestampHeader'),
            new OA\Parameter(ref: '#/components/parameters/XNonceHeader'),
            new OA\Parameter(ref: '#/components/parameters/XSignatureHeader'),
        ],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/SubmitScoreRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 400, ref: '#/components/responses/MissingSignatureHeadersResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/InvalidRequestSignatureResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 409, ref: '#/components/responses/NonceReplayResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/BusinessErrorResponse'),
            new OA\Response(response: 503, ref: '#/components/responses/RequestVerificationUnavailableResponse'),
        ],
    )]
    public function submitScore(): void
    {
    }

    #[OA\Get(
        path: '/api/game/leaderboard',
        operationId: 'getLeaderboard',
        tags: ['Game'],
        summary: 'Get leaderboard entries and current user rank',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/LeaderboardResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
        ],
    )]
    public function leaderboard(): void
    {
    }
}
