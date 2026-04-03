<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthUser',
    type: 'object',
    required: ['id', 'email', 'best_score', 'coins', 'owned_skins_count', 'is_admin'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
        new OA\Property(property: 'best_score', type: 'integer', example: 420),
        new OA\Property(property: 'coins', type: 'integer', example: 180),
        new OA\Property(property: 'active_skin', ref: '#/components/schemas/OwnedSkin', nullable: true),
        new OA\Property(property: 'owned_skins_count', type: 'integer', example: 2),
        new OA\Property(property: 'is_admin', type: 'boolean', example: false),
    ],
)]
#[OA\Schema(
    schema: 'AuthTokenPayload',
    type: 'object',
    required: ['token', 'user'],
    properties: [
        new OA\Property(property: 'token', type: 'string', example: '1|z8qf4J9HfZ2oX1R4lY8mN3pQ6sT7uV9w'),
        new OA\Property(property: 'user', ref: '#/components/schemas/AuthUser'),
    ],
)]
#[OA\Schema(
    schema: 'LogoutPayload',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Logged out successfully.'),
    ],
)]
#[OA\Schema(
    schema: 'AuthTokenEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/AuthTokenPayload'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'AuthUserEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/AuthUser'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'LogoutEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/LogoutPayload'),
            ],
        ),
    ],
)]
class AuthSchemas
{
}
