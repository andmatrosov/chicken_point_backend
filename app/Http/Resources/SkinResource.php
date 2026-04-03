<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Skin
 */
class SkinResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'code' => $this->code,
            'price' => $this->price,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'is_owned' => (bool) ($this->getAttribute('is_owned') ?? false),
            'is_active_for_user' => (bool) ($this->getAttribute('is_active_for_user') ?? false),
        ];
    }
}
