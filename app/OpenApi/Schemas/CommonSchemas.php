<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'EmptyMeta',
    type: 'object',
    additionalProperties: false,
    example: new \ArrayObject([]),
)]
#[OA\Schema(
    schema: 'ApiSuccessEnvelope',
    type: 'object',
    required: ['success', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', type: 'object'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/EmptyMeta'),
    ],
)]
#[OA\Schema(
    schema: 'ValidationErrorEnvelope',
    type: 'object',
    required: ['success', 'message', 'errors'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Validation failed'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
            example: [
                'email' => ['The email field is required.'],
            ],
        ),
    ],
)]
#[OA\Schema(
    schema: 'BusinessErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Not enough coins.'),
        new OA\Property(
            property: 'errors',
            type: 'object',
            nullable: true,
            additionalProperties: new OA\AdditionalProperties(
                type: 'array',
                items: new OA\Items(type: 'string'),
            ),
        ),
    ],
)]
#[OA\Schema(
    schema: 'UnauthenticatedErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Unauthenticated.'),
    ],
)]
#[OA\Schema(
    schema: 'ForbiddenErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Forbidden.'),
    ],
)]
#[OA\Schema(
    schema: 'NotFoundErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Resource not found.'),
    ],
)]
#[OA\Schema(
    schema: 'MissingSignatureHeadersErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Missing required signature headers.'),
    ],
)]
#[OA\Schema(
    schema: 'InvalidSignatureErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Invalid request signature.'),
    ],
)]
#[OA\Schema(
    schema: 'NonceReplayErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Request nonce has already been used.'),
    ],
)]
#[OA\Schema(
    schema: 'RequestVerificationUnavailableEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Request verification is unavailable.'),
    ],
)]
#[OA\Schema(
    schema: 'RateLimitedErrorEnvelope',
    type: 'object',
    required: ['success', 'message'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: false),
        new OA\Property(property: 'message', type: 'string', example: 'Too Many Attempts.'),
    ],
)]
class CommonSchemas
{
}
