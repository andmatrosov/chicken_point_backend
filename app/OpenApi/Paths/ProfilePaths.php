<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class ProfilePaths
{
    #[OA\Get(
        path: '/api/profile',
        operationId: 'getProfile',
        tags: ['Profile'],
        summary: 'Get the authenticated user profile',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function profile(): void
    {
    }

    #[OA\Get(
        path: '/api/profile/skins',
        operationId: 'getOwnedSkins',
        tags: ['Profile'],
        summary: 'Get skins owned by the authenticated user',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/OwnedSkinCollectionResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function skins(): void
    {
    }

    #[OA\Post(
        path: '/api/profile/active-skin',
        operationId: 'setActiveSkin',
        tags: ['Profile'],
        summary: 'Set the active skin for the authenticated user',
        description: 'Requires both Sanctum bearer auth and request-signature headers.',
        security: [['sanctumBearer' => []]],
        parameters: [
            new OA\Parameter(ref: '#/components/parameters/XTimestampHeader'),
            new OA\Parameter(ref: '#/components/parameters/XNonceHeader'),
            new OA\Parameter(ref: '#/components/parameters/XSignatureHeader'),
        ],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/SetActiveSkinRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 400, ref: '#/components/responses/MissingSignatureHeadersResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/SignedRouteUnauthorizedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 409, ref: '#/components/responses/NonceReplayResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/UnprocessableApiResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
            new OA\Response(response: 503, ref: '#/components/responses/RequestVerificationUnavailableResponse'),
        ],
    )]
    public function setActiveSkin(): void
    {
    }

    #[OA\Get(
        path: '/api/profile/rank',
        operationId: 'getCurrentRank',
        tags: ['Profile'],
        summary: 'Get the current leaderboard rank of the authenticated user',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/CurrentRankResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function rank(): void
    {
    }
}
