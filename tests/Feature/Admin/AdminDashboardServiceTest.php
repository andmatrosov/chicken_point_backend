<?php

namespace Tests\Feature\Admin;

use App\Filament\Widgets\ParticipantsByCountryTable;
use App\Models\MvpSetting;
use App\Models\Prize;
use App\Models\User;
use App\Services\AdminDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_service_returns_expected_metrics_statuses_and_country_aggregation(): void
    {
        User::factory()->create([
            'is_admin' => true,
            'country_code' => 'US',
            'country_name' => 'United States',
        ]);

        User::factory()->create([
            'is_admin' => false,
            'country_code' => 'GE',
            'country_name' => 'Georgia',
        ]);

        User::factory()->create([
            'is_admin' => false,
            'country_code' => 'GE',
            'country_name' => 'Georgia',
        ]);

        User::factory()->create([
            'is_admin' => false,
            'country_code' => null,
            'country_name' => null,
        ]);

        Prize::query()->create([
            'title' => 'Активный приз 1',
            'description' => null,
            'quantity' => 10,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Активный приз 2',
            'description' => null,
            'quantity' => 10,
            'default_rank_from' => 2,
            'default_rank_to' => 2,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Неактивный приз',
            'description' => null,
            'quantity' => 10,
            'default_rank_from' => 3,
            'default_rank_to' => 3,
            'is_active' => false,
        ]);

        $this->assertDatabaseHas('mvp_settings', [
            'version' => 'main',
        ]);

        $this->assertDatabaseHas('mvp_settings', [
            'version' => 'brazil',
        ]);

        $this->assertDatabaseMissing('mvp_settings', [
            'version' => 'main',
            'is_active' => true,
        ]);

        MvpSetting::query()
            ->where('version', 'main')
            ->update([
                'mvp_link' => 'https://main.example.com',
                'is_active' => true,
            ]);

        MvpSetting::query()
            ->where('version', 'brazil')
            ->update([
                'mvp_link' => 'https://brazil.example.com',
                'is_active' => false,
            ]);

        /** @var AdminDashboardService $dashboardService */
        $dashboardService = app(AdminDashboardService::class);

        $this->assertSame(3, $dashboardService->getTotalParticipantsCount());
        $this->assertSame(2, $dashboardService->getActivePrizesCount());

        $statuses = $dashboardService->getMvpLinkStatuses()->keyBy('version');

        $this->assertTrue($statuses->has('main'));
        $this->assertTrue($statuses->has('brazil'));
        $this->assertTrue($statuses->get('main')['is_active']);
        $this->assertSame('https://main.example.com', $statuses->get('main')['mvp_link']);
        $this->assertFalse($statuses->get('brazil')['is_active']);
        $this->assertSame('https://brazil.example.com', $statuses->get('brazil')['mvp_link']);

        $countryStats = $dashboardService->getParticipantsByCountryQuery()
            ->orderByDesc('participants_count')
            ->get()
            ->keyBy('country_name_display');

        $this->assertSame(2, (int) $countryStats->get('Georgia')->participants_count);
        $this->assertSame('GE', $countryStats->get('Georgia')->country_code_display);
        $this->assertSame(1, (int) $countryStats->get('Не указано')->participants_count);
        $this->assertSame('—', $countryStats->get('Не указано')->country_code_display);
    }

    public function test_country_table_widget_disables_default_primary_key_sort_for_grouped_query(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        $this->actingAs($admin);

        /** @var ParticipantsByCountryTable $widget */
        $widget = Livewire::test(ParticipantsByCountryTable::class)->instance();
        $query = $widget->getFilteredSortedTableQuery();

        $this->assertNotNull($query);
        $this->assertFalse($widget->getTable()->hasDefaultKeySort());
        $this->assertSame(
            ['participants_count'],
            collect($query->getQuery()->orders ?? [])
                ->pluck('column')
                ->filter(fn ($column): bool => is_string($column))
                ->values()
                ->all(),
        );
    }
}
