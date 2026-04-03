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
        security: [['sanctumBearer' => []]],
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/SetActiveSkinRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserProfileResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/BusinessErrorResponse'),
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
        ],
    )]
    public function rank(): void
    {
    }
}
