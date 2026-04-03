<?php

namespace App\Services;

use App\Models\Skin;
use App\Models\User;
use Illuminate\Support\Collection;

class ShopService
{
    /**
     * @return Collection<int, Skin>
     */
    public function getActiveSkinsForUser(User $user): Collection
    {
        $skins = Skin::query()
            ->where('is_active', true)
            ->orderByRaw('sort_order IS NULL')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $ownedSkinIds = $user->skins()
            ->pluck('skins.id')
            ->all();

        return $skins->map(function (Skin $skin) use ($ownedSkinIds, $user): Skin {
            $skin->setAttribute('is_owned', in_array($skin->id, $ownedSkinIds, true));
            $skin->setAttribute('is_active_for_user', $user->active_skin_id === $skin->id);

            return $skin;
        });
    }
}
