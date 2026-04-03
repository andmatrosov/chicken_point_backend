<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\User
 */
class LeaderboardEntryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'rank' => (int) $this->getAttribute('rank'),
            'score' => (int) $this->best_score,
            'masked_email' => (string) $this->getAttribute('masked_email'),
        ];
    }
}
