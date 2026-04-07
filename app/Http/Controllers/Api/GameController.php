<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetUserRankAction;
use App\Actions\SubmitScoreAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SubmitScoreRequest;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class GameController extends Controller
{
    public function submitScore(
        SubmitScoreRequest $request,
        SubmitScoreAction $submitScoreAction,
        GetUserRankAction $getUserRankAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $payload = $request->payload();
        $updatedUser = $submitScoreAction(
            $user,
            $payload->sessionToken,
            $payload->score,
            $payload->coinsCollected,
            $payload->metadata,
        );

        return $this->successResponse(
            (new UserProfileResource($updatedUser, $getUserRankAction($updatedUser)))->resolve($request),
        );
    }
}
