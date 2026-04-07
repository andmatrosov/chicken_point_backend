<?php

namespace App\Http\Payloads\Game;

final readonly class StartGameSessionPayload
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $metadata = [],
    ) {
    }
}
