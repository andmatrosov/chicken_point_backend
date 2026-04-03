<?php

namespace App\Models;

use App\Services\PrizeRangeValidationService;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Collection<int, UserPrize> $userPrizes
 */
#[Fillable([
    'title',
    'description',
    'quantity',
    'default_rank_from',
    'default_rank_to',
    'is_active',
])]
class Prize extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (Prize $prize): void {
            app(PrizeRangeValidationService::class)->validateForUpsert(
                $prize->getAttributes(),
                $prize->exists ? $prize : null,
            );
        });
    }

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'default_rank_from' => 'integer',
            'default_rank_to' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function userPrizes(): HasMany
    {
        return $this->hasMany(UserPrize::class);
    }
}
