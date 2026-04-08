<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class MvpSettingsPaths
{
    #[OA\Get(
        path: '/api/mvp-settings/main',
        operationId: 'getMainMvpSettings',
        tags: ['MVP Settings'],
        summary: 'Get public MVP settings for the main frontend',
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/MvpSettingResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function main(): void
    {
    }

    #[OA\Get(
        path: '/api/mvp-settings/brazil',
        operationId: 'getBrazilMvpSettings',
        tags: ['MVP Settings'],
        summary: 'Get public MVP settings for the brazil frontend',
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/MvpSettingResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function brazil(): void
    {
    }
}
