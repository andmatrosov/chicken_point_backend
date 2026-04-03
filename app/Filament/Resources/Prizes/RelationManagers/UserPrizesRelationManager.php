<?php

namespace App\Filament\Resources\Prizes\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserPrizesRelationManager extends RelationManager
{
    protected static string $relationship = 'userPrizes';

    protected static ?string $title = 'Assignment History';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('rank_at_assignment')
                    ->label('Rank'),
                IconColumn::make('assigned_manually')
                    ->label('Manual')
                    ->boolean(),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
