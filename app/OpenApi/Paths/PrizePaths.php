<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class PrizePaths
{
    #[OA\Get(
        path: '/api/prizes/my',
        operationId: 'getMyPrizes',
        tags: ['Prizes'],
        summary: 'Get prize assignments for the authenticated user',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/UserPrizeCollectionResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function myPrizes(): void
    {
    }
}
