<?php

namespace App\Models;

use App\Enums\UserPrizeStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'prize_id',
    'rank_at_assignment',
    'assigned_manually',
    'assigned_by',
    'assigned_at',
    'status',
])]
class UserPrize extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'prize_id' => 'integer',
            'rank_at_assignment' => 'integer',
            'assigned_manually' => 'boolean',
            'assigned_by' => 'integer',
            'assigned_at' => 'datetime',
            'status' => UserPrizeStatus::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prize(): BelongsTo
    {
        return $this->belongsTo(Prize::class);
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
