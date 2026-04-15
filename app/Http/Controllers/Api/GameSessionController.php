<?php

namespace App\Http\Controllers\Api;

use App\Actions\CloseGameSessionAction;
use App\Actions\StartGameSessionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CloseGameSessionRequest;
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
            'status' => $gameSession->status->value,
        ]);
    }

    public function close(
        CloseGameSessionRequest $request,
        CloseGameSessionAction $closeGameSessionAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->payload();
        $gameSession = $closeGameSessionAction($user, $payload->sessionToken);

        return $this->successResponse([
            'session_token' => $gameSession->token,
            'status' => $gameSession->status->value,
        ]);
    }
}
