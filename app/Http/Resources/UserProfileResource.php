<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class UserProfileResource extends JsonResource
{
    public function __construct($resource, protected ?int $currentRank = null)
    {
        parent::__construct($resource);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'country_code' => $this->country_code,
            'country_name' => $this->country_name,
            'best_score' => $this->best_score,
            'coins' => $this->coins,
            'active_skin' => $this->whenLoaded(
                'activeSkin',
                fn () => OwnedSkinResource::make($this->activeSkin)->resolve($request),
            ),
            'owned_skins_count' => (int) ($this->skins_count ?? 0),
            'current_rank' => $this->currentRank,
        ];
    }
}
