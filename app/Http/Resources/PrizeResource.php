<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Prize
 */
class PrizeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'quantity' => $this->quantity,
            'default_rank_from' => $this->default_rank_from,
            'default_rank_to' => $this->default_rank_to,
            'is_active' => $this->is_active,
        ];
    }
}
