<?php

namespace App\OpenApi\Responses;

use OpenApi\Attributes as OA;

#[OA\Response(
    response: 'ValidationErrorResponse',
    description: 'Validation failed.',
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
    response: 'MissingSignatureHeadersResponse',
    description: 'The required request-signature headers are missing.',
    content: new OA\JsonContent(ref: '#/components/schemas/MissingSignatureHeadersErrorEnvelope'),
)]
#[OA\Response(
    response: 'InvalidRequestSignatureResponse',
    description: 'The request signature is invalid or the timestamp is outside the accepted skew window.',
    content: new OA\JsonContent(ref: '#/components/schemas/InvalidSignatureErrorEnvelope'),
)]
#[OA\Response(
    response: 'NonceReplayResponse',
    description: 'The signature nonce was already used.',
    content: new OA\JsonContent(ref: '#/components/schemas/NonceReplayErrorEnvelope'),
)]
#[OA\Response(
    response: 'RequestVerificationUnavailableResponse',
    description: 'Request signature verification is enabled but unavailable.',
    content: new OA\JsonContent(ref: '#/components/schemas/RequestVerificationUnavailableEnvelope'),
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
    description: 'Successful response containing the current shop skin list.',
    content: new OA\JsonContent(ref: '#/components/schemas/SkinCollectionEnvelope'),
)]
#[OA\Response(
    response: 'CurrentRankResponse',
    description: 'Successful response containing the current user rank.',
    content: new OA\JsonContent(ref: '#/components/schemas/CurrentRankEnvelope'),
)]
#[OA\Response(
    response: 'SessionStartResponse',
    description: 'Successful response containing a newly issued game session token.',
    content: new OA\JsonContent(ref: '#/components/schemas/SessionStartEnvelope'),
)]
#[OA\Response(
    response: 'LeaderboardResponse',
    description: 'Successful response containing leaderboard entries and the current user rank.',
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
