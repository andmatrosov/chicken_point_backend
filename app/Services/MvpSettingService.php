<?php

namespace App\Services;

use App\Enums\MvpSettingVersion;
use App\Models\MvpSetting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

class MvpSettingService
{
    public function hasTable(): bool
    {
        return Schema::hasTable((new MvpSetting())->getTable());
    }

    public function ensureDefaults(): void
    {
        if (! $this->hasTable()) {
            return;
        }

        foreach (MvpSettingVersion::cases() as $version) {
            MvpSetting::query()->firstOrCreate(
                ['version' => $version->value],
                [
                    'mvp_link' => null,
                    'is_active' => false,
                ],
            );
        }
    }

    public function getByVersion(MvpSettingVersion $version): MvpSetting
    {
        if (! $this->hasTable()) {
            return $this->makeFallbackSetting($version);
        }

        $this->ensureDefaults();

        return MvpSetting::query()
            ->where('version', $version->value)
            ->firstOrFail();
    }

    public function getSafeEmptyQuery(): Builder
    {
        return (new MvpSetting())
            ->newQuery()
            ->fromSub(
                fn ($query) => $query
                    ->selectRaw('null as id, null as version, null as mvp_link, 0 as is_active, null as created_at, null as updated_at')
                    ->whereRaw('1 = 0'),
                'mvp_settings_placeholder',
            );
    }

    protected function makeFallbackSetting(MvpSettingVersion $version): MvpSetting
    {
        return new MvpSetting([
            'version' => $version->value,
            'mvp_link' => null,
            'is_active' => false,
        ]);
    }
}
