<?php

namespace App\Filament\Resources\MvpSettings\Schemas;

use App\Enums\MvpSettingVersion;
use App\Support\AdminPanelLabel;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class MvpSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('MVP настройки')
                    ->schema([
                        TextInput::make('version')
                            ->label('Версия')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(static fn (mixed $state): ?string => AdminPanelLabel::mvpVersion(
                                $state instanceof MvpSettingVersion ? $state : (filled($state) ? (string) $state : null),
                            )),
                        TextInput::make('mvp_link')
                            ->label('MVP ссылка')
                            ->url()
                            ->maxLength(2048)
                            ->required(static fn (Get $get): bool => (bool) $get('is_active')),
                        Toggle::make('is_active')
                            ->label('Ссылка активна')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }
}
