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
        description: 'Creates a new user after trimming and lowercasing the email, validating strict non-DNS email syntax plus device metadata, and returns a Laravel Sanctum personal access token. Bearer tokens are the only API authentication mechanism.',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Register a new player, normalize the email to lowercase, validate strict non-DNS email syntax plus device metadata, and issue a Sanctum bearer token.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/RegisterRequest',
                examples: [
                    new OA\Examples(
                        example: 'invalidEmail',
                        summary: 'Invalid email example',
                        value: [
                            'email' => 'player2@example',
                            'password' => 'secret12345',
                            'password_confirmation' => 'secret12345',
                            'device_id' => 'ios-device-1',
                            'platform' => 'ios',
                            'app_version' => '1.0.0',
                        ],
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 201, ref: '#/components/responses/AuthTokenResponse'),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationErrorEnvelope',
                    examples: [
                        new OA\Examples(
                            example: 'invalidEmailResponse',
                            summary: 'Invalid email response example',
                            value: [
                                'success' => false,
                                'message' => 'Validation error.',
                                'errors' => [
                                    'email' => [
                                        'The email field must be a valid email address.',
                                    ],
                                ],
                            ],
                        ),
                    ],
                ),
            ),
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
        description: 'Authenticates an existing user after trimming and lowercasing the email, validating strict non-DNS email syntax plus device metadata, and returns a Laravel Sanctum personal access token. Bearer tokens are the only API authentication mechanism.',
        requestBody: new OA\RequestBody(
            required: true,
            description: 'Authenticate an existing player after normalizing the email to lowercase and validating strict non-DNS email syntax plus device metadata, then issue a new Sanctum bearer token.',
            content: new OA\JsonContent(
                ref: '#/components/schemas/LoginRequest',
                examples: [
                    new OA\Examples(
                        example: 'invalidEmail',
                        summary: 'Invalid email example',
                        value: [
                            'email' => 'player2@example',
                            'password' => 'secret12345',
                            'device_id' => 'ios-device-1',
                            'platform' => 'ios',
                            'app_version' => '1.0.0',
                        ],
                    ),
                ],
            ),
        ),
        responses: [
            new OA\Response(response: 200, ref: '#/components/responses/AuthTokenResponse'),
            new OA\Response(response: 401, ref: '#/components/responses/BusinessErrorResponse'),
            new OA\Response(
                response: 422,
                description: 'Validation error.',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/ValidationErrorEnvelope',
                    examples: [
                        new OA\Examples(
                            example: 'invalidEmailResponse',
                            summary: 'Invalid email response example',
                            value: [
                                'success' => false,
                                'message' => 'Validation error.',
                                'errors' => [
                                    'email' => [
                                        'The email field must be a valid email address.',
                                    ],
                                ],
                            ],
                        ),
                    ],
                ),
            ),
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
