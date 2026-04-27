<?php

namespace App\Data\Game;

final readonly class ScoreSuspicionResult
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public bool $isSuspicious,
        public bool $isHardSuspicious,
        public int $points,
        public string $reason,
        public array $context = [],
    ) {}

    public static function clean(): self
    {
        return new self(
            isSuspicious: false,
            isHardSuspicious: false,
            points: 0,
            reason: 'none',
            context: [],
        );
    }
}
