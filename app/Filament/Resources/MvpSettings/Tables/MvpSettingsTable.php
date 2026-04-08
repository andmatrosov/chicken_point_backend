<?php

namespace App\Filament\Resources\MvpSettings\Tables;

use App\Enums\MvpSettingVersion;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MvpSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('version')
                    ->formatStateUsing(
                        static fn (mixed $state): ?string => match (true) {
                            $state instanceof MvpSettingVersion => ucfirst($state->value),
                            filled($state) => ucfirst((string) $state),
                            default => null,
                        },
                    )
                    ->searchable(),
                TextColumn::make('mvp_link')
                    ->label('MVP Link')
                    ->placeholder('Not set')
                    ->limit(60),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
