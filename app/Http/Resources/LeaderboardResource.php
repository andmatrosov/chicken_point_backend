<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Collection;

class LeaderboardResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Collection<int, \App\Models\User> $entries */
        $entries = collect($this->resource['entries'] ?? []);

        return [
            'entries' => LeaderboardEntryResource::collection($entries)->resolve($request),
            'current_user_rank' => $this->resource['current_user_rank'] ?? null,
            'current_user_score' => $this->resource['current_user_score'] ?? null,
        ];
    }
}
