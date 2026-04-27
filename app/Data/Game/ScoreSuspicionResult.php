<?php

namespace App\Data\Game;

final readonly class ScoreSuspicionResult
{
    /**
     * @param  array<int, array{reason: string, points: int, level: string, counts_for_points?: bool, category?: string}>  $signals
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public bool $isSuspicious,
        public bool $isHardSuspicious,
        public int $points,
        public string $reason,
        public array $signals = [],
        public array $context = [],
    ) {}

    /**
     * @param  array<int, array{reason: string, points: int, level: string, counts_for_points?: bool, category?: string}>  $signals
     * @param  array<string, mixed>  $context
     */
    public static function fromSignals(array $signals, array $context = []): self
    {
        if ($signals === []) {
            return self::clean();
        }

        $points = array_sum(array_map(
            static fn (array $signal): int => ($signal['counts_for_points'] ?? true)
                ? max(0, (int) ($signal['points'] ?? 0))
                : 0,
            $signals,
        ));

        $hardSignals = array_values(array_filter(
            $signals,
            static fn (array $signal): bool => ($signal['level'] ?? 'soft') === 'hard',
        ));

        $primarySignal = $hardSignals[0] ?? $signals[0];
        $context['points_bearing_reasons'] = array_map(
            static fn (array $signal): string => (string) ($signal['reason'] ?? 'unknown'),
            array_values(array_filter(
                $signals,
                static fn (array $signal): bool => (bool) ($signal['counts_for_points'] ?? true),
            )),
        );
        $context['timing_reasons'] = array_map(
            static fn (array $signal): string => (string) ($signal['reason'] ?? 'unknown'),
            array_values(array_filter(
                $signals,
                static fn (array $signal): bool => ($signal['category'] ?? 'cheat') === 'timing',
            )),
        );

        return new self(
            isSuspicious: true,
            isHardSuspicious: $hardSignals !== [],
            points: $points,
            reason: (string) ($primarySignal['reason'] ?? 'unknown'),
            signals: $signals,
            context: $context,
        );
    }

    public static function clean(): self
    {
        return new self(
            isSuspicious: false,
            isHardSuspicious: false,
            points: 0,
            reason: 'none',
            signals: [],
            context: [],
        );
    }
}
