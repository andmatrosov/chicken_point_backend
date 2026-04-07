<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;

/**
 * @mixin \App\Models\Skin
 */
class OwnedSkinResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $purchasedAt = $this->whenPivotLoaded('user_skins', function (): ?string {
            return $this->pivot?->purchased_at !== null
                ? Carbon::parse($this->pivot->purchased_at)->toISOString()
                : null;
        });

        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'price' => $this->price,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'purchased_at' => $purchasedAt,
        ];
    }
}
