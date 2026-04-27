<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRelatedProfilesTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_returns_other_profiles_with_same_registration_ip_and_excludes_self(): void
    {
        $user = User::factory()->create([
            'registration_ip' => '198.51.100.42',
        ]);

        $relatedOne = User::factory()->create([
            'registration_ip' => '198.51.100.42',
        ]);

        $relatedTwo = User::factory()->create([
            'registration_ip' => '198.51.100.42',
        ]);

        $otherIp = User::factory()->create([
            'registration_ip' => '203.0.113.10',
        ]);

        $relatedIds = $user->relatedProfilesByRegistrationIpRelation()
            ->pluck('id')
            ->all();

        $this->assertSame([$relatedOne->id, $relatedTwo->id], $relatedIds);
        $this->assertNotContains($user->id, $relatedIds);
        $this->assertNotContains($otherIp->id, $relatedIds);
        $this->assertTrue($user->hasRelatedProfilesByRegistrationIp());
    }

    public function test_it_returns_empty_when_registration_ip_is_missing(): void
    {
        $user = User::factory()->create([
            'registration_ip' => null,
        ]);

        User::factory()->create([
            'registration_ip' => null,
        ]);

        $this->assertFalse($user->hasRelatedProfilesByRegistrationIp());
        $this->assertSame([], $user->relatedProfilesByRegistrationIpRelation()->pluck('id')->all());
    }
}
