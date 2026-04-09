<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'RegisterRequest',
    type: 'object',
    required: ['email', 'password', 'password_confirmation', 'device_id', 'platform', 'app_version'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Required. Trimmed, lowercased, validated with a strict non-DNS email format rule, and stored in normalized lowercase form.', example: 'player@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'secret123'),
        new OA\Property(property: 'password_confirmation', type: 'string', example: 'secret123'),
        new OA\Property(property: 'device_id', type: 'string', example: 'ios-device-1'),
        new OA\Property(property: 'platform', type: 'string', enum: ['ios', 'android'], example: 'ios'),
        new OA\Property(property: 'app_version', type: 'string', example: '1.0.0'),
    ],
)]
#[OA\Schema(
    schema: 'LoginRequest',
    type: 'object',
    required: ['email', 'password', 'device_id', 'platform', 'app_version'],
    properties: [
        new OA\Property(property: 'email', type: 'string', format: 'email', description: 'Required. Trimmed, lowercased, and validated with a strict non-DNS email format rule before authentication.', example: 'player@example.com'),
        new OA\Property(property: 'password', type: 'string', example: 'secret123'),
        new OA\Property(property: 'device_id', type: 'string', example: 'android-device-2'),
        new OA\Property(property: 'platform', type: 'string', enum: ['ios', 'android'], example: 'android'),
        new OA\Property(property: 'app_version', type: 'string', example: '2.4.1'),
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
    description: 'Optional client/device context stored with the issued session and enforced during score submission when present.',
    additionalProperties: false,
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
    description: 'Technical/session submission metadata. Only duration, device_id, platform, and app_version are accepted here. Gameplay values such as collected coins must be sent at the top level.',
    additionalProperties: false,
    properties: [
        new OA\Property(property: 'duration', type: 'integer', nullable: true, example: 120),
        new OA\Property(property: 'app_version', type: 'string', nullable: true, example: '1.0.0'),
        new OA\Property(property: 'device_id', type: 'string', nullable: true, example: 'ios-device-1'),
        new OA\Property(property: 'platform', type: 'string', nullable: true, example: 'ios'),
    ],
)]
#[OA\Schema(
    schema: 'SubmitScoreRequest',
    type: 'object',
    description: 'Submit gameplay results for a valid active server-issued session. The session must belong to the authenticated user, remain active, stay within TTL, and be submitted only once. Top-level coins_collected is validated on the server before it is applied to the user balance.',
    required: ['session_token', 'score', 'coins_collected'],
    properties: [
        new OA\Property(property: 'session_token', type: 'string', example: '9af51ca1ff8e4186bdbd52bbf21f664cf9cf78d859602b5e'),
        new OA\Property(property: 'score', type: 'integer', minimum: 0, example: 1337),
        new OA\Property(property: 'coins_collected', type: 'integer', minimum: 0, example: 17),
        new OA\Property(property: 'metadata', ref: '#/components/schemas/SubmitScoreMetadataInput', nullable: true),
    ],
)]
class RequestSchemas
{
}
