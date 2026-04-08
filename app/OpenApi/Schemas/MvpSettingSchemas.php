<?php

namespace App\OpenApi\Schemas;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'MvpSettingData',
    type: 'object',
    required: ['version', 'mvp_link', 'is_active'],
    properties: [
        new OA\Property(property: 'version', type: 'string', enum: ['main', 'brazil'], example: 'main'),
        new OA\Property(property: 'mvp_link', type: 'string', format: 'uri', nullable: true, example: 'https://example.com'),
        new OA\Property(property: 'is_active', type: 'boolean', example: true),
    ],
)]
#[OA\Schema(
    schema: 'MvpSettingEnvelope',
    type: 'object',
    required: ['success', 'data', 'meta'],
    properties: [
        new OA\Property(property: 'success', type: 'boolean', example: true),
        new OA\Property(property: 'data', ref: '#/components/schemas/MvpSettingData'),
        new OA\Property(property: 'meta', ref: '#/components/schemas/EmptyMeta'),
    ],
)]
class MvpSettingSchemas
{
}
