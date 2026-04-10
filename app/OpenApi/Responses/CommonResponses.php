<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationErrorResponse',
    description: 'Validation error.',
    content: new OA\JsonContent(ref: '#/components/schemas/ValidationErrorEnvelope'),
)]
#[OA\Response(
    response: 'BusinessErrorResponse',
    description: 'Business rule violation.',
    content: new OA\JsonContent(ref: '#/components/schemas/BusinessErrorEnvelope'),
)]
#[OA\Response(
    response: 'UnauthenticatedResponse',
    description: 'Authentication is required.',
    content: new OA\JsonContent(ref: '#/components/schemas/UnauthenticatedErrorEnvelope'),
)]
#[OA\Response(
    response: 'ForbiddenResponse',
    description: 'Authenticated user is not allowed to perform this action.',
    content: new OA\JsonContent(ref: '#/components/schemas/ForbiddenErrorEnvelope'),
)]
#[OA\Response(
    response: 'NotFoundResponse',
    description: 'Requested resource was not found.',
    content: new OA\JsonContent(ref: '#/components/schemas/NotFoundErrorEnvelope'),
)]
#[OA\Response(
    response: 'RateLimitedResponse',
    description: 'Too many requests were sent to this endpoint.',
    content: new OA\JsonContent(ref: '#/components/schemas/RateLimitedErrorEnvelope'),
)]
#[OA\Response(
    response: 'UnprocessableApiResponse',
    description: 'The request failed validation or a business rule blocked processing.',
    content: new OA\JsonContent(
        oneOf: [
            new OA\Schema(ref: '#/components/schemas/ValidationErrorEnvelope'),
            new OA\Schema(ref: '#/components/schemas/BusinessErrorEnvelope'),
        ],
    ),
)]
#[OA\Response(
    response: 'AuthTokenResponse',
    description: 'Successful auth response containing a Sanctum token and the authenticated user.',
    content: new OA\JsonContent(ref: '#/components/schemas/AuthTokenEnvelope'),
)]
#[OA\Response(
    response: 'AuthUserResponse',
    description: 'Successful response containing the authenticated user.',
    content: new OA\JsonContent(ref: '#/components/schemas/AuthUserEnvelope'),
)]
#[OA\Response(
    response: 'LogoutResponse',
    description: 'Successful logout response.',
    content: new OA\JsonContent(ref: '#/components/schemas/LogoutEnvelope'),
)]
#[OA\Response(
    response: 'LogoutAllDevicesResponse',
    description: 'Successful response after revoking all bearer tokens for the authenticated user.',
    content: new OA\JsonContent(ref: '#/components/schemas/LogoutAllDevicesEnvelope'),
)]
#[OA\Response(
    response: 'UserProfileResponse',
    description: 'Successful response containing the current user profile.',
    content: new OA\JsonContent(ref: '#/components/schemas/UserProfileEnvelope'),
)]
#[OA\Response(
    response: 'OwnedSkinCollectionResponse',
    description: 'Successful response containing the current user owned skins.',
    content: new OA\JsonContent(ref: '#/components/schemas/OwnedSkinCollectionEnvelope'),
)]
#[OA\Response(
    response: 'SkinCollectionResponse',
    description: 'Successful response containing active shop skins. Guest requests always receive is_owned=false and is_active_for_user=false. Authenticated requests may receive personalized ownership and active-skin flags.',
    content: new OA\JsonContent(
        ref: '#/components/schemas/SkinCollectionEnvelope',
        examples: [
            new OA\Examples(
                example: 'guestShopResponse',
                summary: 'Guest response',
                value: [
                    'success' => true,
                    'data' => [
                        [
                            'id' => 1,
                            'title' => 'Blue Flame',
                            'code' => 'blue-flame',
                            'price' => 200,
                            'image' => null,
                            'is_active' => true,
                            'is_owned' => false,
                            'is_active_for_user' => false,
                        ],
                    ],
                ],
            ),
            new OA\Examples(
                example: 'authenticatedShopResponse',
                summary: 'Authenticated response',
                value: [
                    'success' => true,
                    'data' => [
                        [
                            'id' => 1,
                            'title' => 'Blue Flame',
                            'code' => 'blue-flame',
                            'price' => 200,
                            'image' => null,
                            'is_active' => true,
                            'is_owned' => true,
                            'is_active_for_user' => true,
                        ],
                        [
                            'id' => 2,
                            'title' => 'Red Nova',
                            'code' => 'red-nova',
                            'price' => 350,
                            'image' => null,
                            'is_active' => true,
                            'is_owned' => false,
                            'is_active_for_user' => false,
                        ],
                    ],
                ],
            ),
        ],
    ),
)]
#[OA\Response(
    response: 'CurrentRankResponse',
    description: 'Successful response containing the current user rank.',
    content: new OA\JsonContent(ref: '#/components/schemas/CurrentRankEnvelope'),
)]
#[OA\Response(
    response: 'CountryResponse',
    description: 'Successful response containing the detected request country.',
    content: new OA\JsonContent(ref: '#/components/schemas/CountryEnvelope'),
)]
#[OA\Response(
    response: 'MvpSettingResponse',
    description: 'Successful response containing a public MVP setting record for a specific frontend version.',
    content: new OA\JsonContent(ref: '#/components/schemas/MvpSettingEnvelope'),
)]
#[OA\Response(
    response: 'SessionStartResponse',
    description: 'Successful response containing a newly issued game session token and its server-calculated expiration timestamp.',
    content: new OA\JsonContent(ref: '#/components/schemas/SessionStartEnvelope'),
)]
#[OA\Response(
    response: 'LeaderboardResponse',
    description: 'Successful response containing public leaderboard entries and, for authenticated requests, the current user rank and score.',
    content: new OA\JsonContent(ref: '#/components/schemas/LeaderboardEnvelope'),
)]
#[OA\Response(
    response: 'UserPrizeCollectionResponse',
    description: 'Successful response containing the authenticated user prizes.',
    content: new OA\JsonContent(ref: '#/components/schemas/UserPrizeCollectionEnvelope'),
)]
class CommonResponses
{
}
