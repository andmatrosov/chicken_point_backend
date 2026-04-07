<?php

namespace Tests\Feature\Game;

use App\Models\Skin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_shop_list_returns_active_skins_with_user_flags(): void
    {
        $ownedSkin = Skin::query()->create([
            'title' => 'Owned Skin',
            'code' => 'owned-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $shopSkin = Skin::query()->create([
            'title' => 'Shop Skin',
            'code' => 'shop-skin',
            'price' => 200,
            'image' => null,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Skin::query()->create([
            'title' => 'Inactive Skin',
            'code' => 'inactive-skin',
            'price' => 300,
            'image' => null,
            'is_active' => false,
            'sort_order' => 3,
        ]);

        $user = User::factory()->create([
            'active_skin_id' => $ownedSkin->id,
        ]);

        $user->skins()->attach($ownedSkin->id, ['purchased_at' => now()]);

        $token = $user->createToken('mobile-client')->plainTextToken;

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/game/shop')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.code', 'owned-skin')
            ->assertJsonPath('data.0.is_owned', true)
            ->assertJsonPath('data.0.is_active_for_user', true)
            ->assertJsonPath('data.1.code', 'shop-skin')
            ->assertJsonPath('data.1.is_owned', false)
            ->assertJsonPath('data.1.is_active_for_user', false)
            ->assertJsonMissing(['code' => 'inactive-skin']);

        $this->assertTrue($shopSkin->id > 0);
    }

    public function test_buy_skin_succeeds_and_deducts_coins(): void
    {
        $skin = Skin::query()->create([
            'title' => 'Starter Skin',
            'code' => 'starter-skin',
            'price' => 120,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'coins' => 200,
            'active_skin_id' => null,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/shop/buy-skin', [
            'skin_id' => $skin->id,
        ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.coins', 80)
            ->assertJsonPath('data.active_skin.id', $skin->id)
            ->assertJsonPath('data.owned_skins_count', 1);

        $this->assertDatabaseHas('user_skins', [
            'user_id' => $user->id,
            'skin_id' => $skin->id,
        ]);
        $this->assertSame(80, $user->fresh()->coins);
        $this->assertSame($skin->id, $user->fresh()->active_skin_id);
    }

    public function test_buy_skin_fails_when_user_has_insufficient_coins(): void
    {
        $skin = Skin::query()->create([
            'title' => 'Expensive Skin',
            'code' => 'expensive-skin',
            'price' => 300,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'coins' => 100,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/shop/buy-skin', [
            'skin_id' => $skin->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Not enough coins.')
            ->assertJsonPath('errors.coins.0', 'The user does not have enough coins for this purchase.');
    }

    public function test_buy_skin_fails_when_user_already_owns_it(): void
    {
        $skin = Skin::query()->create([
            'title' => 'Owned Skin',
            'code' => 'owned-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'coins' => 500,
            'active_skin_id' => $skin->id,
        ]);

        $user->skins()->attach($skin->id, ['purchased_at' => now()]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/shop/buy-skin', [
            'skin_id' => $skin->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'You already own this skin.')
            ->assertJsonPath('errors.skin_id.0', 'The selected skin has already been purchased.');
    }

    public function test_buy_skin_fails_when_skin_is_inactive(): void
    {
        $skin = Skin::query()->create([
            'title' => 'Inactive Skin',
            'code' => 'inactive-skin',
            'price' => 50,
            'image' => null,
            'is_active' => false,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'coins' => 500,
        ]);

        $this->bearerJsonAsUser($user, 'POST', '/api/game/shop/buy-skin', [
            'skin_id' => $skin->id,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'This skin is not available for purchase.')
            ->assertJsonPath('errors.skin_id.0', 'The selected skin is inactive.');
    }
}
