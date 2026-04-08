<?php

namespace App\Services;

use App\Enums\MvpSettingVersion;
use App\Models\MvpSetting;
use App\Models\Prize;
use App\Models\User;
use App\Support\AdminPanelLabel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AdminDashboardService
{
    public function getActivePrizesCount(): int
    {
        return Prize::query()
            ->where('is_active', true)
            ->count();
    }

    public function getMvpLinkStatuses(): Collection
    {
        /** @var MvpSettingService $mvpSettingService */
        $mvpSettingService = app(MvpSettingService::class);

        $settingsByVersion = collect();

        if ($mvpSettingService->hasTable()) {
            $mvpSettingService->ensureDefaults();

            $settingsByVersion = MvpSetting::query()
                ->get()
                ->keyBy(fn (MvpSetting $setting): string => $setting->version->value);
        }

        return collect(MvpSettingVersion::cases())
            ->map(function (MvpSettingVersion $version) use ($settingsByVersion): array {
                /** @var MvpSetting|null $setting */
                $setting = $settingsByVersion->get($version->value);

                return [
                    'version' => $version->value,
                    'version_label' => AdminPanelLabel::mvpVersion($version),
                    'is_active' => (bool) ($setting?->is_active ?? false),
                    'mvp_link' => $setting?->mvp_link,
                ];
            })
            ->values();
    }

    public function getParticipantsByCountryQuery(): Builder
    {
        $countryNameExpression = "COALESCE(NULLIF(country_name, ''), ?)";
        $countryCodeExpression = "COALESCE(NULLIF(country_code, ''), ?)";

        return User::query()
            ->where('is_admin', false)
            ->selectRaw('MIN(id) as id')
            ->selectRaw("{$countryNameExpression} as country_name_display", ['Не указано'])
            ->selectRaw("{$countryCodeExpression} as country_code_display", ['—'])
            ->selectRaw('COUNT(*) as participants_count')
            ->groupByRaw($countryNameExpression, ['Не указано'])
            ->groupByRaw($countryCodeExpression, ['—']);
    }

    public function getTotalParticipantsCount(): int
    {
        return User::query()
            ->where('is_admin', false)
            ->count();
    }
}
