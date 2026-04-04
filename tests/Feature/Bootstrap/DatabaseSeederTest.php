<?php

namespace Tests\Feature\Bootstrap;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class DatabaseSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_creates_only_the_documented_local_bootstrap_user(): void
    {
        $this->seed();
        $this->seed();

        $this->assertDatabaseCount('users', 1);
        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'is_admin' => false,
        ]);

        $user = User::query()->where('email', 'test@example.com')->firstOrFail();

        $this->assertTrue(Hash::check('password', $user->password));
    }
}
