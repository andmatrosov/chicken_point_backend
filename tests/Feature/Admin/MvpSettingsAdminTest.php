<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\MvpSettings\Pages\EditMvpSetting;
use App\Filament\Resources\MvpSettings\Pages\ListMvpSettings;
use App\Filament\Resources\MvpSettings\MvpSettingResource;
use App\Models\MvpSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\TestCase;

class MvpSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_see_both_seeded_mvp_settings_records_in_filament(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        $records = MvpSetting::query()
            ->orderByRaw("case version when 'main' then 0 when 'brazil' then 1 else 99 end")
            ->get();

        Livewire::test(ListMvpSettings::class)
            ->assertCanSeeTableRecords($records, inOrder: true);
    }

    public function test_admin_can_update_an_mvp_setting_from_filament(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $setting = MvpSetting::query()->where('version', 'main')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(EditMvpSetting::class, ['record' => $setting->getRouteKey()])
            ->fillForm([
                'mvp_link' => 'https://main.example.com/live',
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('mvp_settings', [
            'id' => $setting->id,
            'version' => 'main',
            'mvp_link' => 'https://main.example.com/live',
            'is_active' => true,
        ]);
    }

    public function test_active_mvp_setting_requires_a_valid_url_in_filament(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $setting = MvpSetting::query()->where('version', 'brazil')->firstOrFail();

        $this->actingAs($admin);

        Livewire::test(EditMvpSetting::class, ['record' => $setting->getRouteKey()])
            ->fillForm([
                'mvp_link' => null,
                'is_active' => true,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'mvp_link' => 'required',
            ]);

        Livewire::test(EditMvpSetting::class, ['record' => $setting->getRouteKey()])
            ->fillForm([
                'mvp_link' => 'not-a-url',
                'is_active' => false,
            ])
            ->call('save')
            ->assertHasFormErrors([
                'mvp_link' => 'url',
            ]);
    }

    public function test_admin_mvp_settings_page_does_not_crash_when_the_table_is_missing(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        Schema::drop('mvp_settings');

        $this->actingAs($admin)
            ->get('/admin/mvp-settings')
            ->assertOk();
    }

    public function test_resource_returns_an_empty_query_when_the_table_is_missing(): void
    {
        Schema::drop('mvp_settings');

        $records = MvpSettingResource::getEloquentQuery()->get();

        $this->assertCount(0, $records);
    }
}
