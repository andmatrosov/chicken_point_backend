<?php

namespace App\OpenApi\Requests;

use OpenApi\Attributes as OA;

#[OA\RequestBody(
    request: 'RegisterRequestBody',
    required: true,
    description: 'Register a new player, normalize the email to lowercase, validate strict non-DNS email syntax plus device metadata, and issue a Sanctum bearer token.',
    content: new OA\JsonContent(ref: '#/components/schemas/RegisterRequest'),
)]
#[OA\RequestBody(
    request: 'LoginRequestBody',
    required: true,
    description: 'Authenticate an existing player after normalizing the email to lowercase and validating strict non-DNS email syntax plus device metadata, then issue a new Sanctum bearer token.',
    content: new OA\JsonContent(ref: '#/components/schemas/LoginRequest'),
)]
#[OA\RequestBody(
    request: 'SetActiveSkinRequestBody',
    required: true,
    description: 'Set the active skin for the authenticated user.',
    content: new OA\JsonContent(ref: '#/components/schemas/SetActiveSkinRequest'),
)]
#[OA\RequestBody(
    request: 'BuySkinRequestBody',
    required: true,
    description: 'Purchase an active skin using the authenticated user coin balance.',
    content: new OA\JsonContent(ref: '#/components/schemas/BuySkinRequest'),
)]
#[OA\RequestBody(
    request: 'StartGameSessionRequestBody',
    required: false,
    description: 'Start a server-issued gameplay session. Optional device metadata may be stored with the session and later enforced on score submission.',
    content: new OA\JsonContent(ref: '#/components/schemas/StartGameSessionRequest'),
)]
#[OA\RequestBody(
    request: 'SubmitScoreRequestBody',
    required: true,
    description: 'Submit score and top-level collected coins for an active server-issued gameplay session. Metadata is restricted to technical/session context only: duration, device_id, platform, and app_version. The server validates collected coins before updating the user balance.',
    content: new OA\JsonContent(ref: '#/components/schemas/SubmitScoreRequest'),
)]
class RequestBodies
{
}
