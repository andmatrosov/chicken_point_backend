<?php

namespace App\Http\Controllers\Api;

use App\Actions\LoginUserAction;
use App\Actions\RegisterUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(
        RegisterRequest $request,
        RegisterUserAction $registerUserAction,
    ): JsonResponse {
        $payload = $registerUserAction($request->validated(), $request->ip());

        return $this->successResponse([
            'token' => $payload['token'],
            'user' => (new AuthUserResource($payload['user']))->resolve($request),
        ], status: 201);
    }

    public function login(
        LoginRequest $request,
        LoginUserAction $loginUserAction,
    ): JsonResponse {
        $payload = $loginUserAction($request->validated());

        return $this->successResponse([
            'token' => $payload['token'],
            'user' => (new AuthUserResource($payload['user']))->resolve($request),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->currentAccessToken()?->delete();

        return $this->successResponse([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request, AuthService $authService): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $this->authorize('viewProfile', $user);

        return $this->successResponse(
            (new AuthUserResource($authService->loadAuthenticatedUser($user)))->resolve($request),
        );
    }
}
