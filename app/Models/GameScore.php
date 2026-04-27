<?php

namespace App\Models;

use App\Support\AdminPanelLabel;
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

    public function serverDurationSeconds(): ?int
    {
        return $this->runtimeStyleServerDurationSeconds();
    }

    public function runtimeStyleServerDurationSeconds(): ?int
    {
        $issuedAt = $this->gameSession?->issued_at;
        $submittedAt = $this->gameSession?->submitted_at;

        if ($issuedAt === null || $submittedAt === null) {
            return null;
        }

        return max(0, $issuedAt->diffInSeconds($submittedAt));
    }

    public function historicalStyleServerDurationSeconds(): ?int
    {
        $issuedAt = $this->gameSession?->issued_at;

        if ($issuedAt === null || $this->created_at === null) {
            return null;
        }

        return max(0, $issuedAt->diffInSeconds($this->created_at));
    }

    public function clientDurationSeconds(): ?int
    {
        $duration = data_get($this->gameSession?->metadata, 'submission.duration');

        return is_int($duration) ? $duration : null;
    }

    public function serverScorePerSecond(): ?float
    {
        return $this->runtimeStyleServerScorePerSecond();
    }

    public function runtimeStyleServerScorePerSecond(): ?float
    {
        $duration = $this->runtimeStyleServerDurationSeconds();

        if ($duration === null || $duration <= 0) {
            return null;
        }

        return round($this->score / $duration, 4);
    }

    public function historicalStyleServerScorePerSecond(): ?float
    {
        $duration = $this->historicalStyleServerDurationSeconds();

        if ($duration === null || $duration <= 0) {
            return null;
        }

        return round($this->score / $duration, 4);
    }

    public function clientScorePerSecond(): ?float
    {
        $duration = $this->clientDurationSeconds();

        if ($duration === null || $duration <= 0) {
            return null;
        }

        return round($this->score / $duration, 4);
    }

    /**
     * @return array<int, array{reason: string, points: int, level: string, counts_for_points?: bool, category?: string}>
     */
    public function suspiciousSignals(): array
    {
        $signals = $this->suspiciousEvent?->signals;

        if (is_array($signals) && $signals !== []) {
            return $signals;
        }

        if ($this->suspiciousEvent !== null && $this->suspiciousEvent->reason !== null) {
            return [[
                'reason' => $this->suspiciousEvent->reason,
                'points' => (int) $this->suspiciousEvent->points,
                'level' => $this->suspiciousEvent->reason === 'adaptive_score_limit_exceeded' ? 'hard' : 'soft',
                'counts_for_points' => ! in_array($this->suspiciousEvent->reason, ['duration_mismatch', 'unreliable_server_duration'], true),
                'category' => in_array($this->suspiciousEvent->reason, ['duration_mismatch', 'unreliable_server_duration'], true) ? 'timing' : 'cheat',
            ]];
        }

        return [];
    }

    /**
     * @return array<int, string>
     */
    public function suspiciousReasons(): array
    {
        return array_map(
            static fn (array $signal): string => (string) ($signal['reason'] ?? 'unknown'),
            $this->suspiciousSignals(),
        );
    }

    public function translatedSuspiciousReasons(): array
    {
        return array_map(
            static fn (string $reason): string => AdminPanelLabel::antiCheatSignal($reason),
            $this->suspiciousReasons(),
        );
    }

    public function suspiciousPoints(): int
    {
        return array_sum(array_map(
            static fn (array $signal): int => ($signal['counts_for_points'] ?? true)
                ? max(0, (int) ($signal['points'] ?? 0))
                : 0,
            $this->suspiciousSignals(),
        ));
    }

    public function suspiciousStatus(): string
    {
        if ($this->suspiciousSignals() === []) {
            return 'none';
        }

        if ($this->hasTimingOnlySignals()) {
            return 'timing_only';
        }

        if ($this->hasPointsBearingCheatSignal()) {
            $serverScorePerSecond = $this->runtimeStyleServerScorePerSecond();

            if ($serverScorePerSecond !== null && $serverScorePerSecond >= 10) {
                return 'critical';
            }

            foreach ($this->suspiciousSignals() as $signal) {
                if (($signal['level'] ?? 'soft') === 'hard') {
                    return 'hard';
                }
            }

            return 'soft';
        }

        return $this->hasTimingAnomaly() ? 'timing_only' : 'none';
    }

    public function hasSuspiciousSignal(string $reason): bool
    {
        foreach ($this->suspiciousSignals() as $signal) {
            if (($signal['reason'] ?? null) === $reason) {
                return true;
            }
        }

        return false;
    }

    public function hasCheatSignal(): bool
    {
        foreach ($this->suspiciousSignals() as $signal) {
            if (($signal['category'] ?? 'cheat') === 'cheat') {
                return true;
            }
        }

        return false;
    }

    public function hasPointsBearingCheatSignal(): bool
    {
        foreach ($this->suspiciousSignals() as $signal) {
            if (($signal['category'] ?? 'cheat') === 'cheat'
                && (bool) ($signal['counts_for_points'] ?? true)) {
                return true;
            }
        }

        return false;
    }

    public function hasTimingAnomaly(): bool
    {
        foreach ($this->suspiciousSignals() as $signal) {
            if (($signal['category'] ?? 'cheat') === 'timing') {
                return true;
            }
        }

        return false;
    }

    public function hasTimingOnlySignals(): bool
    {
        return $this->hasTimingAnomaly() && ! $this->hasPointsBearingCheatSignal();
    }

    public function isServerDurationReliable(): bool
    {
        $persistedStatus = $this->persistedDurationReliabilityStatus();

        if ($persistedStatus !== null) {
            return $persistedStatus === 'reliable';
        }

        return $this->runtimeDurationReliabilityStatus() === 'reliable';
    }

    public function durationReliabilityStatus(): string
    {
        return $this->persistedDurationReliabilityStatus()
            ?? $this->runtimeDurationReliabilityStatus();
    }

    public function persistedDurationReliabilityStatus(): ?string
    {
        $contextStatus = $this->suspiciousEvent?->context['duration_reliability'] ?? null;

        if (is_string($contextStatus) && in_array($contextStatus, ['reliable', 'unreliable'], true)) {
            return $contextStatus;
        }

        if ($this->hasSuspiciousSignal('unreliable_server_duration')) {
            return 'unreliable';
        }

        return null;
    }

    public function runtimeDurationReliabilityStatus(): string
    {
        $serverElapsedSeconds = $this->runtimeStyleServerDurationSeconds();

        if ($serverElapsedSeconds === null) {
            return 'reliable';
        }

        $clientDurationSeconds = $this->clientDurationSeconds();
        $minReliableDurationSeconds = max(0, (int) config('game.anti_cheat.min_reliable_duration_seconds', 5));
        $minClientDurationForValidation = max(0, (int) config('game.anti_cheat.min_client_duration_for_validation', 30));
        $minScoreForDurationValidation = max(0, (int) config('game.anti_cheat.min_score_for_duration_validation', 50));

        if ($serverElapsedSeconds <= $minReliableDurationSeconds && $this->score >= $minScoreForDurationValidation) {
            return 'unreliable';
        }

        if ($clientDurationSeconds !== null
            && $clientDurationSeconds >= $minClientDurationForValidation
            && $serverElapsedSeconds <= $minReliableDurationSeconds) {
            return 'unreliable';
        }

        return 'reliable';
    }

    public function hasTimestampInconsistency(): bool
    {
        $issuedAt = $this->gameSession?->issued_at;
        $submittedAt = $this->gameSession?->submitted_at;

        if ($issuedAt === null || $submittedAt === null) {
            return false;
        }

        return $submittedAt->lt($issuedAt)
            || ($this->created_at !== null && $this->created_at->lt($issuedAt));
    }

    public function sessionDeviceId(): ?string
    {
        return $this->sessionMetadataString('device_id');
    }

    public function sessionPlatform(): ?string
    {
        return $this->sessionMetadataString('platform');
    }

    public function sessionAppVersion(): ?string
    {
        return $this->sessionMetadataString('app_version');
    }

    protected function sessionMetadataString(string $key): ?string
    {
        $value = data_get($this->gameSession?->metadata, 'submission.'.$key)
            ?? data_get($this->gameSession?->metadata, $key);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
