<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\AdminAccountWidget;
use App\Filament\Widgets\AdminOverviewStats;
use App\Filament\Widgets\MvpLinksStatusWidget;
use App\Filament\Widgets\ParticipantsByCountryTable;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static ?string $title = 'Панель управления';

    protected ?string $subheading = 'Метрики проекта и текущие статусы.';

    public function getWidgets(): array
    {
        return [
            AdminAccountWidget::class,
            AdminOverviewStats::class,
            MvpLinksStatusWidget::class,
            ParticipantsByCountryTable::class,
        ];
    }

    public function getColumns(): int|array
    {
        return [
            'md' => 2,
            'xl' => 3,
        ];
    }
}
