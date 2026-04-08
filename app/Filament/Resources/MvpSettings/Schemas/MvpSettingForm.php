<?php

namespace App\Filament\Resources\MvpSettings\Schemas;

use App\Enums\MvpSettingVersion;
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
                Section::make('MVP Settings')
                    ->schema([
                        TextInput::make('version')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(
                                static fn (mixed $state): ?string => match (true) {
                                    $state instanceof MvpSettingVersion => ucfirst($state->value),
                                    filled($state) => ucfirst((string) $state),
                                    default => null,
                                },
                            ),
                        TextInput::make('mvp_link')
                            ->label('MVP Link')
                            ->url()
                            ->maxLength(2048)
                            ->required(static fn (Get $get): bool => (bool) $get('is_active')),
                        Toggle::make('is_active')
                            ->label('MVP Link Active')
                            ->default(false),
                    ])
                    ->columns(1),
            ]);
    }
}
