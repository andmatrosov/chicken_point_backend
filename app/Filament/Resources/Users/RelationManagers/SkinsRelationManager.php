<?php

namespace App\Filament\Resources\Users\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SkinsRelationManager extends RelationManager
{
    protected static string $relationship = 'skins';

    protected static ?string $title = 'Owned Skins';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('price')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('purchased_at')
                    ->label('Purchased at')
                    ->state(fn ($record) => $record->pivot?->purchased_at)
                    ->dateTime(),
            ])
            ->headerActions([])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
