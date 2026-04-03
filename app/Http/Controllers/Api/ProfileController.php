<?php

namespace App\Http\Controllers\Api;

use App\Actions\GetUserRankAction;
use App\Actions\SetActiveSkinAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\SetActiveSkinRequest;
use App\Http\Resources\OwnedSkinResource;
use App\Http\Resources\UserProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function profile(Request $request, GetUserRankAction $getUserRankAction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewProfile', $user);
        $user->load('activeSkin')->loadCount('skins');

        return $this->successResponse(
            (new UserProfileResource($user, $getUserRankAction($user)))->resolve($request),
        );
    }

    public function skins(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewProfile', $user);
        $skins = $user->skins()
            ->select('skins.*')
            ->orderBy('skins.sort_order')
            ->orderBy('skins.id')
            ->get();

        return $this->successResponse(
            OwnedSkinResource::collection($skins)->resolve($request),
        );
    }

    public function setActiveSkin(
        SetActiveSkinRequest $request,
        SetActiveSkinAction $setActiveSkinAction,
        GetUserRankAction $getUserRankAction,
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('updateProfile', $user);
        $updatedUser = $setActiveSkinAction($user, (int) $request->integer('skin_id'));

        return $this->successResponse(
            (new UserProfileResource($updatedUser, $getUserRankAction($updatedUser)))->resolve($request),
        );
    }

    public function rank(Request $request, GetUserRankAction $getUserRankAction): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewProfile', $user);

        return $this->successResponse([
            'current_rank' => $getUserRankAction($user),
        ]);
    }
}
