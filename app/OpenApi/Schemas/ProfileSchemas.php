<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Skin',
    type: 'object',
    required: ['id', 'title', 'code', 'price', 'image', 'is_active', 'is_owned', 'is_active_for_user'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Blue Flame'),
        new OA\Property(property: 'code', type: 'string', example: 'blue-flame'),
        new OA\Property(property: 'price', type: 'integer', example: 200),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'is_owned', type: 'boolean', example: false),
        new OA\Property(property: 'is_active_for_user', type: 'boolean', example: false),
    ],
)]
#[OA\Schema(
    schema: 'OwnedSkin',
    type: 'object',
    required: ['id', 'title', 'code', 'price', 'image', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Blue Flame'),
        new OA\Property(property: 'code', type: 'string', example: 'blue-flame'),
        new OA\Property(property: 'price', type: 'integer', example: 200),
        new OA\Property(property: 'image', type: 'string', nullable: true, example: null),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
        new OA\Property(property: 'purchased_at', type: 'string', format: 'date-time', nullable: true, example: '2026-04-03T10:15:00Z'),
    ],
)]
#[OA\Schema(
    schema: 'UserProfile',
    type: 'object',
    required: ['id', 'email', 'country_code', 'country_name', 'best_score', 'coins', 'owned_skins_count', 'current_rank'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
        new OA\Property(property: 'country_code', type: 'string', nullable: true, example: 'GE'),
        new OA\Property(property: 'country_name', type: 'string', nullable: true, example: 'Georgia'),
        new OA\Property(property: 'best_score', type: 'integer', example: 1337),
        new OA\Property(property: 'coins', type: 'integer', example: 80),
        new OA\Property(property: 'active_skin', ref: '#/components/schemas/OwnedSkin', nullable: true),
        new OA\Property(property: 'owned_skins_count', type: 'integer', example: 2),
        new OA\Property(property: 'current_rank', type: 'integer', nullable: true, example: 3),
    ],
)]
#[OA\Schema(
    schema: 'CountryPayload',
    type: 'object',
    required: ['country_code', 'country_name'],
    properties: [
        new OA\Property(property: 'country_code', type: 'string', nullable: true, example: 'GE'),
        new OA\Property(property: 'country_name', type: 'string', nullable: true, example: 'Georgia'),
    ],
)]
#[OA\Schema(
    schema: 'CurrentRankPayload',
    type: 'object',
    required: ['current_rank'],
    properties: [
        new OA\Property(property: 'current_rank', type: 'integer', nullable: true, example: 3),
    ],
)]
#[OA\Schema(
    schema: 'UserProfileEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/UserProfile'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'OwnedSkinCollectionEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/OwnedSkin'),
                ),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'SkinCollectionEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Skin'),
                ),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'CountryEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/CountryPayload'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'CurrentRankEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/CurrentRankPayload'),
            ],
        ),
    ],
)]
class ProfileSchemas
{
}
