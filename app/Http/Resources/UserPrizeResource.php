<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\UserPrize
 */
class UserPrizeResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status?->value,
            'assigned_at' => $this->assigned_at?->toISOString(),
            'rank_at_assignment' => $this->rank_at_assignment,
            'assigned_manually' => $this->assigned_manually,
            'prize' => $this->whenLoaded(
                'prize',
                fn () => PrizeResource::make($this->prize)->resolve($request),
            ),
        ];
    }
}
