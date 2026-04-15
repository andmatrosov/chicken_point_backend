<?php

namespace App\Http\Payloads\Game;

final readonly class CloseGameSessionPayload
{
    public function __construct(
        public string $sessionToken,
    ) {
    }
}
