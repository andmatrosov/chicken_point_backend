<?php

namespace Tests;

use App\Models\User;
use App\Services\RequestSignatureService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    protected function signedJsonAsUser(
        User $user,
        string $method,
        string $uri,
        array $payload = [],
        ?string $nonce = null,
        ?int $timestamp = null,
        ?string $signature = null,
    ): TestResponse {
        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;
        $timestamp ??= now()->timestamp;
        $nonce ??= 'nonce-'.str()->random(16);
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature ??= app(RequestSignatureService::class)->sign(
            $method,
            $uri,
            $body,
            (string) $timestamp,
            $nonce,
        );

        return $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withHeader('X-Timestamp', (string) $timestamp)
            ->withHeader('X-Nonce', $nonce)
            ->withHeader('X-Signature', $signature)
            ->json($method, $uri, $payload);
    }
}
