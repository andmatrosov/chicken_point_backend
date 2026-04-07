<?php

namespace App\Http\Controllers\Api;

use App\Actions\StartGameSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\StartGameSessionRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class GameSessionController extends Controller
{
    public function start(
        StartGameSessionRequest $request,
        StartGameSessionAction $startGameSessionAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->payload();
        $gameSession = $startGameSessionAction($user, $payload->metadata);

        return $this->successResponse([
            'session_token' => $gameSession->token,
            'expires_at' => $gameSession->expires_at->toISOString(),
        ]);
    }
}
