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
                    ->sortable(),
                TextColumn::make('adminUser.email')
                    ->label('Admin user')
                    ->searchable(),
                TextColumn::make('action')
                    ->searchable(),
                TextColumn::make('entity_type')
                    ->searchable(),
                TextColumn::make('entity_id')
                    ->sortable(),
                TextColumn::make('created_at')
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
