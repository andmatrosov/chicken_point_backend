<?php

namespace App\Http\Payloads\Game;

final readonly class SubmitScorePayload
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $sessionToken,
        public int $score,
        public int $coinsCollected,
        public array $metadata = [],
    ) {
    }
}
