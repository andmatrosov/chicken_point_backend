<?php

namespace App\Http\Controllers\Api;

use App\Actions\BuySkinAction;
use App\Actions\GetUserRankAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\BuySkinRequest;
use App\Http\Resources\SkinResource;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use App\Services\ShopService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    public function index(Request $request, ShopService $shopService): JsonResponse
    {
        /** @var User|null $user */
        $user = $request->user('sanctum');

        return $this->successResponse(
            SkinResource::collection($shopService->getActiveSkinsForUser($user))->resolve($request),
        );
    }

    public function buy(
        BuySkinRequest $request,
        BuySkinAction $buySkinAction,
        GetUserRankAction $getUserRankAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $updatedUser = $buySkinAction($user, (int) $request->integer('skin_id'));

        return $this->successResponse(
            (new UserProfileResource($updatedUser, $getUserRankAction($updatedUser)))->resolve($request),
        );
    }
}
