<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'score',
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
            'is_processed' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
