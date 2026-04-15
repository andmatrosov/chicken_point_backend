<?php

namespace App\Actions;

use App\Models\GameSession;
use App\Models\User;
use App\Services\GameSessionService;

class CloseGameSessionAction
{
    public function __construct(
        protected GameSessionService $gameSessionService,
    ) {
    }

    public function __invoke(User $user, string $sessionToken): GameSession
    {
        return $this->gameSessionService->closeSession($user, $sessionToken);
    }
}
