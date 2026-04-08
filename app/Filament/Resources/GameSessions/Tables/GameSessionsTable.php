<?php

namespace App\Filament\Resources\GameSessions\Tables;

use App\Support\AdminPanelLabel;
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
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Участник')
                    ->searchable(),
                TextColumn::make('token')
                    ->label('Токен')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (mixed $state): ?string => AdminPanelLabel::gameSessionStatus($state))
                    ->badge(),
                TextColumn::make('issued_at')
                    ->label('Выдана')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label('Истекает')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label('Отправлена')
                    ->dateTime()
                    ->placeholder('Еще не отправлена'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'active' => 'Активна',
                        'submitted' => 'Отправлена',
                        'expired' => 'Истекла',
                        'canceled' => 'Отменена',
                    ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
