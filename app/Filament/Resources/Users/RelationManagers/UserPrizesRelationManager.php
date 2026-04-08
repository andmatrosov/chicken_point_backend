<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use App\Support\AdminPanelLabel;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserPrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'userPrizes';

    protected static ?string $title = 'Призы';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('prize.title')
                    ->label('Приз')
                    ->formatStateUsing(fn (?string $state, UserPrize $record): ?string => $record->status === UserPrizeStatus::CANCELED ? null : $state)
                    ->placeholder('Активный приз отсутствует')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (UserPrizeStatus|string|null $state): ?string => AdminPanelLabel::userPrizeStatus($state))
                    ->badge(),
                TextColumn::make('rank_at_assignment')
                    ->label('Ранг'),
                IconColumn::make('assigned_manually')
                    ->label('Вручную')
                    ->boolean(),
                TextColumn::make('assignedBy.email')
                    ->label('Назначил')
                    ->placeholder('Система'),
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
