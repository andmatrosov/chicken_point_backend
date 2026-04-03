<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Gate;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;

/**
 * @property int $id
 * @property string $email
 * @property string $password
 * @property int $best_score
 * @property int $coins
 * @property int|null $active_skin_id
 * @property int|null $last_rank_cached
 * @property bool $is_admin
 * @property-read Skin|null $activeSkin
 * @property-read Collection<int, Skin> $skins
 * @property-read Collection<int, UserSkin> $userSkins
 * @property-read Collection<int, GameScore> $scores
 * @property-read Collection<int, GameSession> $gameSessions
 * @property-read Collection<int, UserPrize> $userPrizes
 */
#[Fillable([
    'email',
    'password',
    'best_score',
    'coins',
    'active_skin_id',
    'last_rank_cached',
    'is_admin',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'best_score' => 'integer',
            'coins' => 'integer',
            'active_skin_id' => 'integer',
            'last_rank_cached' => 'integer',
            'is_admin' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function activeSkin(): BelongsTo
    {
        return $this->belongsTo(Skin::class, 'active_skin_id');
    }

    public function skins(): BelongsToMany
    {
        return $this->belongsToMany(Skin::class, 'user_skins')
            ->withPivot(['id', 'purchased_at'])
            ->withTimestamps();
    }

    public function userSkins(): HasMany
    {
        return $this->hasMany(UserSkin::class);
    }

    public function scores(): HasMany
    {
        return $this->hasMany(GameScore::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function userPrizes(): HasMany
    {
        return $this->hasMany(UserPrize::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return Gate::forUser($this)->allows('access-admin-panel');
    }

    public function getFilamentName(): string
    {
        return $this->email;
    }
}
