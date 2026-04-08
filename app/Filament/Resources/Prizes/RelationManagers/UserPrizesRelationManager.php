<?php

namespace App\Filament\Resources\Prizes\RelationManagers;

use App\Support\AdminPanelLabel;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserPrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'userPrizes';

    protected static ?string $title = 'История назначений';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Участник')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (mixed $state): ?string => AdminPanelLabel::userPrizeStatus($state))
                    ->badge(),
                TextColumn::make('rank_at_assignment')
                    ->label('Ранг'),
                IconColumn::make('assigned_manually')
                    ->label('Вручную')
                    ->boolean(),
                TextColumn::make('assigned_at')
                    ->label('Назначен')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
