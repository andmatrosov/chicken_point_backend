<?php

namespace Tests\Feature\Profile;

use App\Models\Skin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_endpoint_returns_the_authenticated_users_profile_summary(): void
    {
        $activeSkin = Skin::query()->create([
            'title' => 'Active Skin',
            'code' => 'active-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $ownedSkin = Skin::query()->create([
            'title' => 'Owned Skin',
            'code' => 'owned-skin',
            'price' => 120,
            'image' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $higherUser = User::factory()->create([
            'best_score' => 900,
        ]);

        $this->assertTrue($higherUser->id > 0);

        $user = User::factory()->create([
            'email' => 'player@example.com',
            'country_code' => 'GE',
            'country_name' => 'Georgia',
            'best_score' => 700,
            'coins' => 150,
            'active_skin_id' => $activeSkin->id,
        ]);

        $user->skins()->attach($activeSkin->id, ['purchased_at' => now()]);
        $user->skins()->attach($ownedSkin->id, ['purchased_at' => now()]);

        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.email', 'player@example.com')
            ->assertJsonPath('data.country_code', 'GE')
            ->assertJsonPath('data.country_name', 'Georgia')
            ->assertJsonPath('data.best_score', 700)
            ->assertJsonPath('data.coins', 150)
            ->assertJsonPath('data.owned_skins_count', 2)
            ->assertJsonPath('data.current_rank', 2)
            ->assertJsonPath('data.active_skin.id', $activeSkin->id)
            ->assertJsonPath('data.active_skin.code', 'active-skin');
    }

    public function test_owned_skins_endpoint_returns_only_the_authenticated_users_owned_skins(): void
    {
        $firstSkin = Skin::query()->create([
            'title' => 'First Skin',
            'code' => 'first-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $secondSkin = Skin::query()->create([
            'title' => 'Second Skin',
            'code' => 'second-skin',
            'price' => 150,
            'image' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $otherSkin = Skin::query()->create([
            'title' => 'Other Skin',
            'code' => 'other-skin',
            'price' => 180,
            'image' => null,
            'is_active' => true,
            'sort_order' => 3,
        ]);

        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $user->skins()->attach($firstSkin->id, ['purchased_at' => now()->subDay()]);
        $user->skins()->attach($secondSkin->id, ['purchased_at' => now()]);
        $otherUser->skins()->attach($otherSkin->id, ['purchased_at' => now()]);

        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/profile/skins')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'first-skin')
            ->assertJsonPath('data.1.code', 'second-skin')
            ->assertJsonMissing(['code' => 'other-skin']);
    }

    public function test_set_active_skin_updates_the_authenticated_users_active_skin(): void
    {
        $firstSkin = Skin::query()->create([
            'title' => 'First Skin',
            'code' => 'first-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $secondSkin = Skin::query()->create([
            'title' => 'Second Skin',
            'code' => 'second-skin',
            'price' => 200,
            'image' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create([
            'active_skin_id' => $firstSkin->id,
        ]);

        $user->skins()->attach($firstSkin->id, ['purchased_at' => now()->subDay()]);
        $user->skins()->attach($secondSkin->id, ['purchased_at' => now()]);

        $this->bearerJsonAsUser($user, 'POST', '/api/profile/active-skin', [
                'skin_id' => $secondSkin->id,
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.active_skin.id', $secondSkin->id)
            ->assertJsonPath('data.active_skin.code', 'second-skin');

        $this->assertSame($secondSkin->id, $user->fresh()->active_skin_id);
    }

    public function test_set_active_skin_rejects_unowned_skins(): void
    {
        $ownedSkin = Skin::query()->create([
            'title' => 'Owned Skin',
            'code' => 'owned-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $unownedSkin = Skin::query()->create([
            'title' => 'Unowned Skin',
            'code' => 'unowned-skin',
            'price' => 200,
            'image' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $user = User::factory()->create([
            'active_skin_id' => $ownedSkin->id,
        ]);

        $user->skins()->attach($ownedSkin->id, ['purchased_at' => now()]);

        $this->bearerJsonAsUser($user, 'POST', '/api/profile/active-skin', [
                'skin_id' => $unownedSkin->id,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You can only activate skins you own.')
            ->assertJsonPath('errors.skin_id.0', 'The selected skin is not owned by the user.');
    }
}
