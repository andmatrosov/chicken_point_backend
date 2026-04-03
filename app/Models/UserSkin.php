<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'user_id',
    'skin_id',
    'purchased_at',
])]
class UserSkin extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'skin_id' => 'integer',
            'purchased_at' => 'datetime',
        ];
    }
}
