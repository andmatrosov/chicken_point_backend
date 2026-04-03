<?php

namespace App\Filament\Resources\GameSessions\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class GameSessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('issued_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('token')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('issued_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->placeholder('Not submitted'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'submitted' => 'Submitted',
                        'expired' => 'Expired',
                        'canceled' => 'Canceled',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
