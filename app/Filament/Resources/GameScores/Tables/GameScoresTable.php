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
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('score')
                    ->sortable(),
                TextColumn::make('session_token')
                    ->searchable()
                    ->copyable(),
                IconColumn::make('is_processed')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_processed')
                    ->label('Processed'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
