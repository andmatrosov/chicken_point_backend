<?php

namespace App\OpenApi;

use OpenApi\Attributes as OA;

#[OA\Info(
    version: '1.5.0',
    title: 'Game Backend API',
    description: 'Public mobile game API for authentication, country detection, MVP settings, profile, shop, gameplay sessions, leaderboard, and prizes.',
)]
#[OA\Server(
    url: '/',
    description: 'Current application host',
)]
#[OA\Tag(
    name: 'Auth',
    description: 'Registration, login, logout, and current-user endpoints.',
)]
#[OA\Tag(
    name: 'Profile',
    description: 'Current user profile, owned skins, and rank endpoints.',
)]
#[OA\Tag(
    name: 'GeoIP',
    description: 'Public endpoint for request IP country detection.',
)]
#[OA\Tag(
    name: 'MVP Settings',
    description: 'Public frontend-specific MVP link settings.',
)]
#[OA\Tag(
    name: 'Shop',
    description: 'Skin shop listing and purchases.',
)]
#[OA\Tag(
    name: 'Game',
    description: 'Game session issuing, score submission, and leaderboard.',
)]
#[OA\Tag(
    name: 'Prizes',
    description: 'Current user prize assignments.',
)]
class OpenApiSpec
{
}
