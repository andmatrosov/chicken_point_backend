<?php

namespace App\Filament\Widgets;

use App\Services\AdminDashboardService;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ParticipantsByCountryTable extends TableWidget
{
    protected static bool $isLazy = false;

    protected static ?int $sort = 0;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        /** @var AdminDashboardService $dashboardService */
        $dashboardService = app(AdminDashboardService::class);

        return $table
            ->query($dashboardService->getParticipantsByCountryQuery())
            ->heading('Количество участников по странам')
            ->defaultSort('participants_count', 'desc')
            ->defaultKeySort(false)
            ->columns([
                TextColumn::make('country_name_display')
                    ->label('Страна'),
                TextColumn::make('country_code_display')
                    ->label('Код')
                    ->badge(),
                TextColumn::make('participants_count')
                    ->label('Участников')
                    ->numeric()
                    ->sortable(),
            ])
            ->paginated(false)
            ->emptyStateHeading('Данные по странам отсутствуют')
            ->emptyStateDescription('Статистика появится после регистрации участников с определенной страной.')
            ->striped();
    }
}
