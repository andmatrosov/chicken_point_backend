<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserPrizeResource;
use App\Models\User;
use App\Services\PrizeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PrizeController extends Controller
{
    public function myPrizes(Request $request, PrizeService $prizeService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewPrizes', $user);

        return $this->successResponse(
            UserPrizeResource::collection($prizeService->getUserPrizes($user))->resolve($request),
        );
    }
}
