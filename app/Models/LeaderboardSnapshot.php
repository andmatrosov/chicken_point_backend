<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'kind',
    'is_active',
    'captured_at',
    'source_hash',
    'payload',
    'frozen_by_user_id',
    'frozen_at',
    'cleared_by_user_id',
    'cleared_at',
])]
class LeaderboardSnapshot extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'captured_at' => 'datetime',
            'payload' => 'array',
            'frozen_by_user_id' => 'integer',
            'frozen_at' => 'datetime',
            'cleared_by_user_id' => 'integer',
            'cleared_at' => 'datetime',
        ];
    }

    public function frozenByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'frozen_by_user_id');
    }

    public function clearedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by_user_id');
    }
}
