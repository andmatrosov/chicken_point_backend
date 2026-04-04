<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class UserTableSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_table_search_still_works_by_email(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $matchingUser = User::factory()->create([
            'email' => 'geo-search@example.com',
            'registration_ip' => '203.0.113.10',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'second@example.com',
            'registration_ip' => '198.51.100.20',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->searchTable('geo-search@example.com')
            ->assertCanSeeTableRecords([$matchingUser])
            ->assertCanNotSeeTableRecords([$otherUser]);
    }

    public function test_users_table_search_works_by_ipv4_registration_ip(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $matchingUser = User::factory()->create([
            'email' => 'ipv4@example.com',
            'registration_ip' => '198.51.100.42',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other-ipv4@example.com',
            'registration_ip' => '198.51.100.99',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->searchTable('198.51.100.42')
            ->assertCanSeeTableRecords([$matchingUser])
            ->assertCanNotSeeTableRecords([$otherUser]);
    }

    public function test_users_table_search_works_by_ipv6_registration_ip(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $matchingUser = User::factory()->create([
            'email' => 'ipv6@example.com',
            'registration_ip' => '2001:db8:1::10',
        ]);

        $otherUser = User::factory()->create([
            'email' => 'other-ipv6@example.com',
            'registration_ip' => '2001:db8:2::20',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->searchTable('2001:db8:1::')
            ->assertCanSeeTableRecords([$matchingUser])
            ->assertCanNotSeeTableRecords([$otherUser]);
    }

    public function test_users_table_exact_ip_search_returns_only_exact_match(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $matchingUser = User::factory()->create([
            'registration_ip' => '198.51.100.42',
        ]);

        $similarUser = User::factory()->create([
            'registration_ip' => '198.51.100.421',
        ]);

        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->searchTable('198.51.100.42')
            ->assertCanSeeTableRecords([$matchingUser])
            ->assertCanNotSeeTableRecords([$similarUser]);
    }
}
