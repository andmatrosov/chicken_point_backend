<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function bearerJsonAsUser(
        User $user,
        string $method,
        string $uri,
        array $payload = [],
    ): TestResponse {
        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        return $this->bearerJson($method, $uri, $payload, $plainTextToken);
    }

    protected function bearerJson(
        string $method,
        string $uri,
        array $payload,
        string $plainTextToken,
    ): TestResponse {
        return $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->json($method, $uri, $payload);
    }
}
