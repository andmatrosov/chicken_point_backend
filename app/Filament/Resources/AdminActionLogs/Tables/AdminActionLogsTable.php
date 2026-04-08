<?php

namespace App\Filament\Resources\AdminActionLogs\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminActionLogsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('adminUser.email')
                    ->label('Администратор')
                    ->searchable(),
                TextColumn::make('action')
                    ->label('Действие')
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->label('Тип сущности')
                    ->searchable(),
                TextColumn::make('entity_id')
                    ->label('ID сущности')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([]);
    }
}
