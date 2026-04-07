<?php

namespace App\OpenApi\Paths;

use OpenApi\Attributes as OA;

class AuthPaths
{
    #[OA\Post(
        path: '/api/auth/register',
        operationId: 'registerPlayer',
        tags: ['Auth'],
        summary: 'Register a new player and issue a bearer token',
        description: 'Creates a new user, validates device metadata, and returns a Laravel Sanctum personal access token. Bearer tokens are the only API authentication mechanism.',
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/RegisterRequestBody'),
        responses: [
            new OA\Response(response: 201, ref: '#/components/responses/AuthTokenResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationErrorResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function register(): void
    {
    }

    #[OA\Post(
        path: '/api/auth/login',
        operationId: 'loginPlayer',
        tags: ['Auth'],
        summary: 'Authenticate a player and issue a bearer token',
        description: 'Authenticates an existing user, validates device metadata, and returns a Laravel Sanctum personal access token. Bearer tokens are the only API authentication mechanism.',
        requestBody: new OA\RequestBody(ref: '#/components/requestBodies/LoginRequestBody'),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/AuthTokenResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/BusinessErrorResponse'),
            new OA\Response(response: 422, ref: '#/components/responses/ValidationErrorResponse'),
            new OA\Response(response: 429, ref: '#/components/responses/RateLimitedResponse'),
        ],
    )]
    public function login(): void
    {
    }

    #[OA\Post(
        path: '/api/auth/logout',
        operationId: 'logoutPlayer',
        tags: ['Auth'],
        summary: 'Revoke the current bearer token',
        description: 'Revokes only the Sanctum bearer token used for the current request.',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/LogoutResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
        ],
    )]
    public function logout(): void
    {
    }

    #[OA\Post(
        path: '/api/auth/logout-all-devices',
        operationId: 'logoutPlayerFromAllDevices',
        tags: ['Auth'],
        summary: 'Revoke all bearer tokens for the authenticated user',
        description: 'Revokes every active Sanctum bearer token that belongs to the authenticated user.',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/LogoutAllDevicesResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
        ],
    )]
    public function logoutAllDevices(): void
    {
    }

    #[OA\Get(
        path: '/api/me',
        operationId: 'getAuthenticatedUser',
        tags: ['Auth'],
        summary: 'Get the authenticated user',
        security: [['sanctumBearer' => []]],
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/AuthUserResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/UnauthenticatedResponse'),
            new OA\Response(response: 403, ref: '#/components/responses/ForbiddenResponse'),
        ],
    )]
    public function me(): void
    {
    }
}
