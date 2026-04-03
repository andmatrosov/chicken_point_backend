<?php

namespace App\Actions;

use App\Exceptions\BusinessException;
use App\Models\Skin;
use App\Models\User;

class SetActiveSkinAction
{
    public function __invoke(User $user, int $skinId): User
    {
        $skin = Skin::query()->findOrFail($skinId);

        if (! $user->skins()->whereKey($skinId)->exists()) {
            throw new BusinessException(
                'You can only activate skins you own.',
                errors: ['skin_id' => ['The selected skin is not owned by the user.']],
            );
        }

        if (! $skin->is_active) {
            throw new BusinessException(
                'Inactive skins cannot be activated.',
                errors: ['skin_id' => ['The selected skin is inactive.']],
            );
        }

        $user->forceFill([
            'active_skin_id' => $skin->id,
        ])->save();

        return $user->fresh()->load('activeSkin')->loadCount('skins');
    }
}
