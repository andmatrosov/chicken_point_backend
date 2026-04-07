<?php

namespace App\OpenApi\Security;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'sanctumBearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum PAT',
    description: 'Laravel Sanctum personal access token. Send as Authorization: Bearer <token>.',
)]
class SecurityDefinitions
{
}
