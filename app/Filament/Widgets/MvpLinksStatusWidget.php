<?php

namespace App\Filament\Widgets;

use App\Services\AdminDashboardService;
use Filament\Widgets\Widget;

class MvpLinksStatusWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = -1;

    protected string $view = 'filament.widgets.mvp-links-status-widget';

    protected int|string|array $columnSpan = [
        'default' => 'full',
        'xl' => 1,
    ];

    protected function getViewData(): array
    {
        /** @var AdminDashboardService $dashboardService */
        $dashboardService = app(AdminDashboardService::class);

        return [
            'statuses' => $dashboardService->getMvpLinkStatuses(),
        ];
    }
}
