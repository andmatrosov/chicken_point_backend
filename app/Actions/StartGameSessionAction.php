<?php

namespace App\Actions;

use App\Models\GameSession;
use App\Models\User;
use App\Services\GameSessionService;

class StartGameSessionAction
{
    public function __construct(
        protected GameSessionService $gameSessionService,
    ) {
    }

    public function __invoke(User $user, array $metadata = []): GameSession
    {
        return $this->gameSessionService->startSession($user, $metadata);
    }
}
