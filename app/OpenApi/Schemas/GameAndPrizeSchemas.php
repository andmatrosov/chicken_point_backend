<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'GameSessionPayload',
    type: 'object',
    required: ['session_token', 'status'],
    properties: [
        new OA\Property(property: 'session_token', type: 'string', example: '9af51ca1ff8e4186bdbd52bbf21f664cf9cf78d859602b5e'),
        new OA\Property(property: 'status', type: 'string', example: 'active'),
    ],
)]
#[OA\Schema(
    schema: 'LeaderboardEntry',
    type: 'object',
    required: ['rank', 'score', 'masked_email'],
    properties: [
        new OA\Property(property: 'rank', type: 'integer', example: 1),
        new OA\Property(property: 'score', type: 'integer', example: 1500),
        new OA\Property(property: 'masked_email', type: 'string', example: 'pl***@example.com'),
    ],
)]
#[OA\Schema(
    schema: 'LeaderboardPayload',
    type: 'object',
    required: ['entries'],
    properties: [
        new OA\Property(
            property: 'entries',
            type: 'array',
            items: new OA\Items(ref: '#/components/schemas/LeaderboardEntry'),
        ),
        new OA\Property(
            property: 'current_user_rank',
            type: 'integer',
            nullable: true,
            example: 4,
            description: 'Included only when the request is authenticated with a Sanctum token.',
        ),
        new OA\Property(
            property: 'current_user_score',
            type: 'integer',
            nullable: true,
            example: 980,
            description: 'Included only when the request is authenticated with a Sanctum token.',
        ),
    ],
)]
#[OA\Schema(
    schema: 'Prize',
    type: 'object',
    required: ['id', 'title', 'description', 'quantity', 'default_rank_from', 'default_rank_to', 'is_active'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 1),
        new OA\Property(property: 'title', type: 'string', example: 'Gold Trophy'),
        new OA\Property(property: 'description', type: 'string', nullable: true, example: 'Top leaderboard reward'),
        new OA\Property(property: 'quantity', type: 'integer', example: 5),
        new OA\Property(property: 'default_rank_from', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'default_rank_to', type: 'integer', nullable: true, example: 1),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'UserPrize',
    type: 'object',
    required: ['id', 'status', 'assigned_at', 'rank_at_assignment', 'assigned_manually', 'prize'],
    properties: [
        new OA\Property(property: 'id', type: 'integer', example: 10),
        new OA\Property(property: 'status', type: 'string', example: 'pending'),
        new OA\Property(property: 'assigned_at', type: 'string', format: 'date-time', nullable: true, example: '2026-04-03T10:45:00Z'),
        new OA\Property(property: 'rank_at_assignment', type: 'integer', nullable: true, example: 2),
        new OA\Property(property: 'assigned_manually', type: 'boolean', example: false),
        new OA\Property(property: 'prize', ref: '#/components/schemas/Prize'),
    ],
)]
#[OA\Schema(
    schema: 'SessionStartEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/GameSessionPayload'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'SessionCloseEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/GameSessionPayload'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'LeaderboardEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(property: 'data', ref: '#/components/schemas/LeaderboardPayload'),
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'UserPrizeCollectionEnvelope',
    allOf: [
        new OA\Schema(ref: '#/components/schemas/ApiSuccessEnvelope'),
        new OA\Schema(
            properties: [
                new OA\Property(
                    property: 'data',
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/UserPrize'),
                ),
            ],
        ),
    ],
)]
class GameAndPrizeSchemas
{
}
