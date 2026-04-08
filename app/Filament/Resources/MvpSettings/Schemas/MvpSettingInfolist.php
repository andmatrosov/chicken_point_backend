<?php

namespace App\Filament\Resources\MvpSettings\Schemas;

use App\Enums\MvpSettingVersion;
use App\Support\AdminPanelLabel;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class MvpSettingInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('MVP настройки')
                    ->schema([
                        TextEntry::make('version')
                            ->label('Версия')
                            ->formatStateUsing(static fn (mixed $state): ?string => AdminPanelLabel::mvpVersion(
                                $state instanceof MvpSettingVersion ? $state : (filled($state) ? (string) $state : null),
                            )),
                        TextEntry::make('mvp_link')
                            ->label('MVP ссылка')
                            ->url(static fn (?string $state): ?string => $state)
                            ->placeholder('Не указана'),
                        IconEntry::make('is_active')
                            ->label('Ссылка активна')
                            ->boolean(),
                    ])
                    ->columns(1),
            ]);
    }
}
