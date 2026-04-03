<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'best_score' => $this->best_score,
            'coins' => $this->coins,
            'active_skin' => $this->whenLoaded(
                'activeSkin',
                fn () => OwnedSkinResource::make($this->activeSkin)->resolve($request),
            ),
            'owned_skins_count' => (int) ($this->skins_count ?? 0),
            'is_admin' => $this->is_admin,
        ];
    }
}
