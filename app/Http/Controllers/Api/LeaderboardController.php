<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\LeaderboardResource;
use App\Models\User;
use App\Services\LeaderboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaderboardController extends Controller
{
    public function index(Request $request, LeaderboardService $leaderboardService): JsonResponse
    {
        /** @var User|null $user */
        // NOTE:
        // This controller intentionally resolves an OPTIONAL Sanctum user.
        // The route itself is public, but authenticated requests may include
        // user-specific leaderboard data.
        $user = $request->user('sanctum');

        return $this->successResponse(
            (new LeaderboardResource($leaderboardService->getLeaderboardData($user)))->resolve($request),
        );
    }
}
