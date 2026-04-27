<?php

namespace App\Models;

use App\Enums\UserPrizeStatus;
use App\Services\AdminAccessSafetyService;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
 * @property string|null $registration_ip
 * @property string|null $country_code
 * @property string|null $country_name
 * @property string $password
 * @property int $best_score
 * @property int $coins
 * @property int|null $active_skin_id
 * @property int|null $last_rank_cached
 * @property bool $is_admin
 * @property bool $has_suspicious_game_results
 * @property int $suspicious_game_result_points
 * @property \Illuminate\Support\Carbon|null $suspicious_game_results_flagged_at
 * @property string|null $suspicious_game_results_reason
 * @property-read Skin|null $activeSkin
 * @property-read Collection<int, Skin> $skins
 * @property-read Collection<int, UserSkin> $userSkins
 * @property-read Collection<int, GameScore> $scores
 * @property-read Collection<int, UserSuspiciousEvent> $suspiciousEvents
 * @property-read Collection<int, GameSession> $gameSessions
 * @property-read Collection<int, UserPrize> $userPrizes
 * @property-read UserPrize|null $currentPrizeAssignment
 */
#[Fillable([
    'email',
    'registration_ip',
    'country_code',
    'country_name',
    'password',
    'best_score',
    'coins',
    'active_skin_id',
    'last_rank_cached',
    'is_admin',
    'has_suspicious_game_results',
    'suspicious_game_result_points',
    'suspicious_game_results_flagged_at',
    'suspicious_game_results_reason',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected static function booted(): void
    {
        static::updating(function (self $user): void {
            /** @var self|null $actor */
            $actor = auth()->user();

            if (! ($actor instanceof self)) {
                $actor = null;
            }

            app(AdminAccessSafetyService::class)->assertAdminDemotionAllowed(
                target: $user,
                wasAdmin: (bool) $user->getOriginal('is_admin'),
                newIsAdmin: (bool) $user->is_admin,
                actor: $actor,
            );
        });
    }

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
            'has_suspicious_game_results' => 'boolean',
            'suspicious_game_result_points' => 'integer',
            'suspicious_game_results_flagged_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected function email(): Attribute
    {
        return Attribute::make(
            set: static fn (mixed $value): mixed => is_string($value)
                ? mb_strtolower(trim($value))
                : $value,
        );
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

    public function relatedProfilesByRegistrationIpRelation(): HasMany
    {
        return $this->hasMany(self::class, 'registration_ip', 'registration_ip')
            ->whereNotNull('registration_ip')
            ->whereKeyNot($this->getKey());
    }

    public function suspiciousEvents(): HasMany
    {
        return $this->hasMany(UserSuspiciousEvent::class);
    }

    public function userPrizes(): HasMany
    {
        return $this->hasMany(UserPrize::class);
    }

    public function currentPrizeAssignment(): HasOne
    {
        return $this->hasOne(UserPrize::class)
            ->ofMany(
                ['assigned_at' => 'max', 'id' => 'max'],
                fn ($query) => $query->whereIn('status', [
                    UserPrizeStatus::PENDING,
                    UserPrizeStatus::ISSUED,
                ]),
            );
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return Gate::forUser($this)->allows('access-admin-panel');
    }

    /**
     * @return Collection<int, self>
     */
    public function relatedProfilesByRegistrationIp(): Collection
    {
        if (! filled($this->registration_ip)) {
            return new Collection();
        }

        return self::query()
            ->select([
                'id',
                'email',
                'registration_ip',
                'best_score',
                'country_name',
                'has_suspicious_game_results',
                'created_at',
            ])
            ->where('registration_ip', $this->registration_ip)
            ->whereKeyNot($this->getKey())
            ->orderBy('created_at')
            ->get();
    }

    public function hasRelatedProfilesByRegistrationIp(): bool
    {
        if (! filled($this->registration_ip)) {
            return false;
        }

        return self::query()
            ->where('registration_ip', $this->registration_ip)
            ->whereKeyNot($this->getKey())
            ->exists();
    }

    public function getFilamentName(): string
    {
        return $this->email;
    }
}
