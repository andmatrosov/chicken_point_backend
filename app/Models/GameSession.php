<?php

namespace App\Models;

use App\Enums\GameSessionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'token',
    'status',
    'issued_at',
    'expires_at',
    'submitted_at',
    'metadata',
])]
class GameSession extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'status' => GameSessionStatus::class,
            'issued_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
