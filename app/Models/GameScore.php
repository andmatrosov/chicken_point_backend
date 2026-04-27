<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'score',
    'coins_collected',
    'session_token',
    'is_processed',
])]
class GameScore extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'score' => 'integer',
            'coins_collected' => 'integer',
            'is_processed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'session_token', 'token');
    }

    public function suspiciousEvent(): HasOne
    {
        return $this->hasOne(UserSuspiciousEvent::class);
    }
}
