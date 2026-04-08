<?php

namespace Tests\Feature\MvpSettings;

use App\Enums\MvpSettingVersion;
use App\Models\MvpSetting;
use App\Services\MvpSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MvpSettingsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_main_endpoint_returns_the_standard_envelope_with_active_mvp_data(): void
    {
        MvpSetting::query()
            ->where('version', 'main')
            ->update([
                'mvp_link' => 'https://main.example.com/mvp',
                'is_active' => true,
            ]);

        $this->getJson('/api/mvp-settings/main')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 'main')
            ->assertJsonPath('data.mvp_link', 'https://main.example.com/mvp')
            ->assertJsonPath('data.is_active', true)
            ->assertJsonStructure([
                'success',
                'data' => ['version', 'mvp_link', 'is_active'],
                'meta',
            ]);
    }

    public function test_brazil_endpoint_returns_inactive_data_when_the_link_is_not_enabled(): void
    {
        MvpSetting::query()
            ->where('version', 'brazil')
            ->update([
                'mvp_link' => null,
                'is_active' => false,
            ]);

        $this->getJson('/api/mvp-settings/brazil')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 'brazil')
            ->assertJsonPath('data.mvp_link', null)
            ->assertJsonPath('data.is_active', false)
            ->assertJsonStructure([
                'success',
                'data' => ['version', 'mvp_link', 'is_active'],
                'meta',
            ]);
    }

    public function test_public_mvp_endpoints_recreate_missing_default_records_on_first_use(): void
    {
        MvpSetting::query()->delete();

        $this->getJson('/api/mvp-settings/main')
            ->assertOk()
            ->assertJsonPath('data.version', 'main')
            ->assertJsonPath('data.mvp_link', null)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseCount('mvp_settings', 2);
        $this->assertDatabaseHas('mvp_settings', ['version' => 'main']);
        $this->assertDatabaseHas('mvp_settings', ['version' => 'brazil']);
    }

    public function test_service_returns_a_safe_fallback_when_the_table_is_missing(): void
    {
        Schema::drop('mvp_settings');

        $setting = app(MvpSettingService::class)->getByVersion(MvpSettingVersion::MAIN);

        $this->assertFalse($setting->exists);
        $this->assertSame('main', $setting->version->value);
        $this->assertNull($setting->mvp_link);
        $this->assertFalse($setting->is_active);
    }

    public function test_service_recreates_missing_default_records_when_the_table_exists(): void
    {
        MvpSetting::query()->delete();

        app(MvpSettingService::class)->ensureDefaults();

        $this->assertDatabaseCount('mvp_settings', 2);
        $this->assertDatabaseHas('mvp_settings', ['version' => 'main']);
        $this->assertDatabaseHas('mvp_settings', ['version' => 'brazil']);
    }

    public function test_public_endpoint_returns_a_safe_default_when_the_table_is_missing(): void
    {
        Schema::drop('mvp_settings');

        $this->getJson('/api/mvp-settings/main')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.version', 'main')
            ->assertJsonPath('data.mvp_link', null)
            ->assertJsonPath('data.is_active', false);
    }
}
