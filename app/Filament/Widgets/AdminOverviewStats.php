<?php

namespace App\Filament\Widgets;

use App\Services\AdminDashboardService;
use Filament\Widgets\Widget;

class AdminOverviewStats extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = -2;

    protected string $view = 'filament.widgets.admin-overview-stats';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 2,
    ];

    protected function getViewData(): array
    {
        /** @var AdminDashboardService $dashboardService */
        $dashboardService = app(AdminDashboardService::class);

        return [
            'participantsCount' => $dashboardService->getTotalParticipantsCount(),
            'activePrizesCount' => $dashboardService->getActivePrizesCount(),
        ];
    }
}
