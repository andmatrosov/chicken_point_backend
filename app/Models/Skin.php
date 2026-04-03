<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $title
 * @property string $code
 * @property int $price
 * @property string|null $image
 * @property bool $is_active
 * @property int|null $sort_order
 * @property-read Collection<int, UserSkin> $userSkins
 * @property-read Collection<int, User> $users
 */
#[Fillable([
    'title',
    'code',
    'price',
    'image',
    'is_active',
    'sort_order',
])]
class Skin extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function userSkins(): HasMany
    {
        return $this->hasMany(UserSkin::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_skins')
            ->withPivot(['id', 'purchased_at'])
            ->withTimestamps();
    }
}
