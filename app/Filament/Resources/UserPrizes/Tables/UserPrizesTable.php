<?php

namespace App\Filament\Resources\UserPrizes\Tables;

use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserPrizesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('prize.title')
                    ->label('Prize')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('rank_at_assignment')
                    ->label('Rank'),
                IconColumn::make('assigned_manually')
                    ->label('Manual')
                    ->boolean(),
                TextColumn::make('assignedBy.email')
                    ->label('Assigned by')
                    ->placeholder('System'),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
