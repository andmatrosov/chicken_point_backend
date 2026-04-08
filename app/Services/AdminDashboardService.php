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
        $normalizedUsers = User::query()
            ->where('is_admin', false)
            ->select('id')
            ->selectRaw("COALESCE(NULLIF(country_name, ''), ?) as country_name_display", ['Не указано'])
            ->selectRaw("COALESCE(NULLIF(country_code, ''), ?) as country_code_display", ['—']);

        return User::query()
            ->fromSub($normalizedUsers, 'country_participants')
            ->selectRaw('MIN(country_participants.id) as id')
            ->addSelect('country_name_display', 'country_code_display')
            ->selectRaw('COUNT(*) as participants_count')
            ->groupBy('country_name_display', 'country_code_display');
    }

    public function getTotalParticipantsCount(): int
    {
        return User::query()
            ->where('is_admin', false)
            ->count();
    }
}
