<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'AuthUser',
    type: 'object',
    required: ['id', 'email', 'country_code', 'country_name', 'best_score', 'coins', 'owned_skins_count', 'is_admin'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
        new OA\Property(property: 'country_code', type: 'string', nullable: true, example: 'GE'),
        new OA\Property(property: 'country_name', type: 'string', nullable: true, example: 'Georgia'),
        new OA\Property(property: 'restricted', type: 'boolean', example: true, description: 'Present only when the account has the suspicious-results flag and is restricted from leaderboard-based participation.'),
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
    schema: 'LogoutAllDevicesPayload',
    type: 'object',
    required: ['message'],
    properties: [
        new OA\Property(property: 'message', type: 'string', example: 'Logged out from all devices successfully.'),
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
#[OA\Schema(
    schema: 'LogoutAllDevicesEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/LogoutAllDevicesPayload'),
            ],
        ),
    ],
)]
class AuthSchemas
{
}
