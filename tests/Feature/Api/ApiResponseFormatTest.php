<?php

namespace Tests\Feature\Api;

use App\Enums\UserPrizeStatus;
use App\Models\Prize;
use App\Models\User;
use App\Models\UserPrize;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiResponseFormatTest extends TestCase
{
    use RefreshDatabase;

    public function test_success_responses_use_the_standard_envelope(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'email' => 'format@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.user.email', 'format@example.com')
            ->assertJsonStructure([
                'success',
                'data',
                'meta',
            ]);
    }

    public function test_leaderboard_endpoint_uses_the_standard_envelope_and_resource_payload(): void
    {
        User::factory()->create([
            'email' => 'alpha@example.com',
            'best_score' => 1000,
        ]);

        $currentUser = User::factory()->create([
            'email' => 'viewer@example.com',
            'best_score' => 500,
        ]);

        $plainTextToken = $currentUser->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/game/leaderboard');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'entries' => [
                        '*' => ['rank', 'score', 'masked_email'],
                    ],
                    'current_user_rank',
                    'current_user_score',
                ],
                'meta',
            ])
            ->assertJsonMissingPath('data.entries.0.email');
    }

    public function test_prizes_endpoint_uses_the_standard_envelope_and_resource_payload(): void
    {
        $user = User::factory()->create();

        $prize = Prize::query()->create([
            'title' => 'Envelope Prize',
            'description' => 'Resource payload check.',
            'quantity' => 1,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        UserPrize::query()->create([
            'user_id' => $user->id,
            'prize_id' => $prize->id,
            'rank_at_assignment' => 1,
            'assigned_manually' => false,
            'assigned_by' => null,
            'assigned_at' => now(),
            'status' => UserPrizeStatus::PENDING,
        ]);

        $plainTextToken = $user->createToken('mobile-client')->plainTextToken;

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$plainTextToken)
            ->getJson('/api/prizes/my');

        $response
            ->assertOk()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'status',
                        'assigned_at',
                        'rank_at_assignment',
                        'assigned_manually',
                        'prize' => ['id', 'title', 'description', 'quantity', 'default_rank_from', 'default_rank_to', 'is_active'],
                    ],
                ],
                'meta',
            ])
            ->assertJsonMissingPath('data.0.user_id')
            ->assertJsonMissingPath('data.0.assigned_by');
    }

    public function test_validation_errors_use_the_standard_envelope(): void
    {
        $response = $this->postJson('/api/auth/register', []);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonStructure([
                'success',
                'message',
                'errors' => [
                    'email',
                    'password',
                ],
            ]);
    }

    public function test_business_errors_use_the_standard_envelope(): void
    {
        User::factory()->create([
            'email' => 'player@example.com',
            'password' => 'secret123',
        ]);

        $response = $this->postJson('/api/auth/login', [
            'email' => 'player@example.com',
            'password' => 'wrong-password',
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Invalid credentials.')
            ->assertJsonPath('errors.email.0', 'The provided credentials are incorrect.');
    }

    public function test_unauthenticated_errors_use_the_standard_envelope(): void
    {
        $response = $this->getJson('/api/me');

        $response
            ->assertUnauthorized()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthenticated.');
    }

    public function test_not_found_errors_use_the_standard_envelope(): void
    {
        $response = $this->getJson('/api/unknown-endpoint');

        $response
            ->assertNotFound()
            ->assertHeader('content-type', 'application/json')
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Resource not found.');
    }

    public function test_non_api_not_found_is_not_forced_into_api_json_rendering(): void
    {
        $response = $this->get('/missing-page');

        $response
            ->assertNotFound()
            ->assertHeader('content-type', 'text/html; charset=UTF-8');
    }
}
