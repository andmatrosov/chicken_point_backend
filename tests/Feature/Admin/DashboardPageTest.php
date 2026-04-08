<?php

namespace Tests\Feature\Admin;

use App\Models\MvpSetting;
use App\Models\Prize;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_is_localized_to_russian_and_displays_core_project_metrics(): void
    {
        $admin = User::factory()->create([
            'is_admin' => true,
        ]);

        User::factory()->count(2)->create([
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
            'title' => 'Приз 1',
            'description' => null,
            'quantity' => 5,
            'default_rank_from' => 1,
            'default_rank_to' => 1,
            'is_active' => true,
        ]);

        Prize::query()->create([
            'title' => 'Приз 2',
            'description' => null,
            'quantity' => 5,
            'default_rank_from' => 2,
            'default_rank_to' => 2,
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
                'mvp_link' => null,
                'is_active' => false,
            ]);

        $response = $this->actingAs($admin)
            ->get('/admin');

        $response
            ->assertOk()
            ->assertSeeText('Панель управления')
            ->assertSeeText('Добро пожаловать')
            ->assertSeeText('Количество участников')
            ->assertSeeText('Активные призы')
            ->assertSeeText('Статусы активных ссылок MVP')
            ->assertSeeText('Количество участников по странам')
            ->assertSeeText('3')
            ->assertSeeText('2')
            ->assertSeeText('Main')
            ->assertSeeText('Brazil')
            ->assertSeeText('Активна')
            ->assertSeeText('Неактивна')
            ->assertSeeText('Georgia')
            ->assertSeeText('GE')
            ->assertSeeText('Не указано');
    }
}
