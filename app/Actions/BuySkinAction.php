<?php

namespace App\Actions;

use App\Exceptions\BusinessException;
use App\Models\Skin;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BuySkinAction
{
    public function __invoke(User $user, int $skinId): User
    {
        return DB::transaction(function () use ($user, $skinId): User {
            $lockedSkin = Skin::query()
                ->whereKey($skinId)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $lockedSkin->is_active) {
                throw new BusinessException(
                    'This skin is not available for purchase.',
                    errors: ['skin_id' => ['The selected skin is inactive.']],
                );
            }

            $lockedUser = User::query()
                ->whereKey($user->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedUser->skins()->whereKey($lockedSkin->id)->exists()) {
                throw new BusinessException(
                    'You already own this skin.',
                    errors: ['skin_id' => ['The selected skin has already been purchased.']],
                );
            }

            if ($lockedUser->coins < $lockedSkin->price) {
                throw new BusinessException(
                    'Not enough coins.',
                    errors: ['coins' => ['The user does not have enough coins for this purchase.']],
                );
            }

            $lockedUser->userSkins()->create([
                'skin_id' => $lockedSkin->id,
                'purchased_at' => now(),
            ]);

            $lockedUser->coins -= $lockedSkin->price;

            if (
                $lockedUser->active_skin_id === null
                && (bool) config('game.shop.auto_activate_first_skin', true)
            ) {
                $lockedUser->active_skin_id = $lockedSkin->id;
            }

            $lockedUser->save();

            return $lockedUser->fresh()->load('activeSkin')->loadCount('skins');
        });
    }
}
