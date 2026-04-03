<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserPrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'userPrizes';

    protected static ?string $title = 'Prizes';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('prize.title')
                    ->label('Prize')
                    ->formatStateUsing(fn (?string $state, UserPrize $record): ?string => $record->status === UserPrizeStatus::CANCELED ? null : $state)
                    ->placeholder('No active prize')
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
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
