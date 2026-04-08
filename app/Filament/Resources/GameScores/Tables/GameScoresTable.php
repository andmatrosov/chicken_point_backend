<?php

namespace App\Filament\Resources\GameScores\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class GameScoresTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Участник')
                    ->searchable(),
                TextColumn::make('score')
                    ->label('Счет')
                    ->sortable(),
                TextColumn::make('coins_collected')
                    ->label('Собрано монет')
                    ->numeric(decimalPlaces: 0)
                    ->sortable(),
                TextColumn::make('session_token')
                    ->label('Токен сессии')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_processed')
                    ->label('Обработан')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_processed')
                    ->label('Обработан'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
