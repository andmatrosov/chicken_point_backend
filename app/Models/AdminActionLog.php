<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'admin_user_id',
    'action',
    'entity_type',
    'entity_id',
    'payload',
])]
class AdminActionLog extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'admin_user_id' => 'integer',
            'entity_id' => 'integer',
            'payload' => 'array',
        ];
    }

    public function adminUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }
}
