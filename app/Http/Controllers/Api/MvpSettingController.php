<?php

namespace App\Http\Controllers\Api;

use App\Enums\MvpSettingVersion;
use App\Http\Controllers\Controller;
use App\Http\Resources\MvpSettingResource;
use App\Services\MvpSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MvpSettingController extends Controller
{
    public function main(Request $request, MvpSettingService $mvpSettingService): JsonResponse
    {
        return $this->showVersion($request, $mvpSettingService, MvpSettingVersion::MAIN);
    }

    public function brazil(Request $request, MvpSettingService $mvpSettingService): JsonResponse
    {
        return $this->showVersion($request, $mvpSettingService, MvpSettingVersion::BRAZIL);
    }

    protected function showVersion(
        Request $request,
        MvpSettingService $mvpSettingService,
        MvpSettingVersion $version,
    ): JsonResponse {
        return $this->successResponse(
            (new MvpSettingResource($mvpSettingService->getByVersion($version)))->resolve($request),
        );
    }
}
