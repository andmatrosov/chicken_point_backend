<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    type: 'object',
    required: ['email', 'password', 'password_confirmation'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'secret123'),
        new OA\Property(property: 'password_confirmation', type: 'string', example: 'secret123'),
        new OA\Property(property: 'device_id', type: 'string', nullable: true, example: 'ios-device-1'),
        new OA\Property(property: 'platform', type: 'string', nullable: true, example: 'ios'),
        new OA\Property(property: 'app_version', type: 'string', nullable: true, example: '1.0.0'),
    ],
)]
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', example: 'player@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'secret123'),
        new OA\Property(property: 'device_id', type: 'string', nullable: true, example: 'android-device-2'),
        new OA\Property(property: 'platform', type: 'string', nullable: true, example: 'android'),
        new OA\Property(property: 'app_version', type: 'string', nullable: true, example: '2.4.1'),
    ],
)]
#[OA\Schema(
    schema: 'SetActiveSkinRequest',
    type: 'object',
    required: ['skin_id'],
    properties: [
        new OA\Property(property: 'skin_id', type: 'integer', example: 2),
    ],
)]
#[OA\Schema(
    schema: 'BuySkinRequest',
    type: 'object',
    required: ['skin_id'],
    properties: [
        new OA\Property(property: 'skin_id', type: 'integer', example: 3),
    ],
)]
#[OA\Schema(
    schema: 'StartGameSessionMetadataInput',
    type: 'object',
    properties: [
        new OA\Property(property: 'device_id', type: 'string', nullable: true, example: 'ios-device-1'),
        new OA\Property(property: 'app_version', type: 'string', nullable: true, example: '1.0.0'),
        new OA\Property(property: 'platform', type: 'string', nullable: true, example: 'ios'),
    ],
)]
#[OA\Schema(
    schema: 'StartGameSessionRequest',
    type: 'object',
    properties: [
        new OA\Property(property: 'metadata', ref: '#/components/schemas/StartGameSessionMetadataInput', nullable: true),
    ],
)]
#[OA\Schema(
    schema: 'SubmitScoreMetadataInput',
    type: 'object',
    description: 'Gameplay metadata. Coins are submitted separately via coins_collected and are not derived from score.',
    properties: [
        new OA\Property(property: 'duration', type: 'integer', nullable: true, example: 120),
        new OA\Property(property: 'coins_collected', type: 'integer', nullable: true, example: 8),
        new OA\Property(property: 'app_version', type: 'string', nullable: true, example: '1.0.0'),
        new OA\Property(property: 'device_id', type: 'string', nullable: true, example: 'ios-device-1'),
    ],
)]
#[OA\Schema(
    schema: 'SubmitScoreRequest',
    type: 'object',
    description: 'Submit score and separately collected gameplay coins for a valid active session.',
    required: ['session_token', 'score'],
    properties: [
        new OA\Property(property: 'session_token', type: 'string', example: '9af51ca1ff8e4186bdbd52bbf21f664cf9cf78d859602b5e'),
        new OA\Property(property: 'score', type: 'integer', minimum: 0, example: 1337),
        new OA\Property(property: 'metadata', ref: '#/components/schemas/SubmitScoreMetadataInput', nullable: true),
    ],
)]
class RequestSchemas
{
}
