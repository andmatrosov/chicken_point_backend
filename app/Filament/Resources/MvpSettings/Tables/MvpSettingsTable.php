<?php

namespace App\Filament\Resources\MvpSettings\Tables;

use App\Enums\MvpSettingVersion;
use App\Support\AdminPanelLabel;
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
                    ->label('Версия')
                    ->formatStateUsing(static fn (mixed $state): ?string => AdminPanelLabel::mvpVersion(
                        $state instanceof MvpSettingVersion ? $state : (filled($state) ? (string) $state : null),
                    ))
                    ->searchable(),
                TextColumn::make('mvp_link')
                    ->label('MVP ссылка')
                    ->placeholder('Не указана')
                    ->limit(60),
                IconColumn::make('is_active')
                    ->label('Активна')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Обновлена')
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
