<?php

namespace Tests\Unit;

use App\Actions\BuySkinAction;
use App\Exceptions\BusinessException;
use App\Models\Skin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BuySkinActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_purchases_a_skin_deducts_coins_and_auto_activates_the_first_owned_skin(): void
    {
        config()->set('game.shop.auto_activate_first_skin', true);

        $skin = Skin::query()->create([
            'title' => 'Starter Skin',
            'code' => 'starter-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'coins' => 250,
            'active_skin_id' => null,
        ]);

        $updatedUser = app(BuySkinAction::class)($user, $skin->id);

        $this->assertSame(150, $updatedUser->coins);
        $this->assertSame($skin->id, $updatedUser->active_skin_id);
        $this->assertDatabaseHas('user_skins', [
            'user_id' => $user->id,
            'skin_id' => $skin->id,
        ]);
    }

    public function test_it_rejects_duplicate_skin_purchases(): void
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
            'coins' => 250,
            'active_skin_id' => $skin->id,
        ]);

        $user->skins()->attach($skin->id, ['purchased_at' => now()]);

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('You already own this skin.');

        app(BuySkinAction::class)($user, $skin->id);
    }

    public function test_it_rejects_a_skin_that_is_inactive_in_current_db_state(): void
    {
        $skin = Skin::query()->create([
            'title' => 'Limited Skin',
            'code' => 'limited-skin',
            'price' => 100,
            'image' => null,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $user = User::factory()->create([
            'coins' => 250,
            'active_skin_id' => null,
        ]);

        $skin->forceFill([
            'is_active' => false,
        ])->save();

        $this->expectException(BusinessException::class);
        $this->expectExceptionMessage('This skin is not available for purchase.');

        app(BuySkinAction::class)($user, $skin->id);
    }
}
