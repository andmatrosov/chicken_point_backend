<?php

namespace App\OpenApi\Security;

use OpenApi\Attributes as OA;

#[OA\SecurityScheme(
    securityScheme: 'sanctumBearer',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Bearer',
    description: 'Laravel Sanctum token. Send as Authorization: Bearer <token>.',
)]
#[OA\Parameter(
    parameter: 'XTimestampHeader',
    name: 'X-Timestamp',
    in: 'header',
    required: true,
    description: 'Unix timestamp used for HMAC request signing. Required on signed mutation endpoints.',
    schema: new OA\Schema(type: 'string', example: '1765171200'),
)]
#[OA\Parameter(
    parameter: 'XNonceHeader',
    name: 'X-Nonce',
    in: 'header',
    required: true,
    description: 'Unique nonce used for replay protection. Required on signed mutation endpoints.',
    schema: new OA\Schema(type: 'string', example: '9f8c0c7d3e2b44a6'),
)]
#[OA\Parameter(
    parameter: 'XSignatureHeader',
    name: 'X-Signature',
    in: 'header',
    required: true,
    description: 'HMAC-SHA256 signature of METHOD|PATH|BODY|TIMESTAMP|NONCE. Required on signed mutation endpoints.',
    schema: new OA\Schema(type: 'string', example: '7f6a3f9b8724c4b4f3db49c0a7778d2c298f1f888fdbf6d5a6f52e92f1b03eaa'),
)]
class SecurityDefinitions
{
}
