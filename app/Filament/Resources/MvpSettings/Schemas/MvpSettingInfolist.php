<?php

namespace App\Filament\Resources\MvpSettings\Schemas;

use App\Enums\MvpSettingVersion;
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
                Section::make('MVP Settings')
                    ->schema([
                        TextEntry::make('version')
                            ->formatStateUsing(
                                static fn (mixed $state): ?string => match (true) {
                                    $state instanceof MvpSettingVersion => ucfirst($state->value),
                                    filled($state) => ucfirst((string) $state),
                                    default => null,
                                },
                            ),
                        TextEntry::make('mvp_link')
                            ->label('MVP Link')
                            ->url(static fn (?string $state): ?string => $state)
                            ->placeholder('Not set'),
                        IconEntry::make('is_active')
                            ->label('MVP Link Active')
                            ->boolean(),
                    ])
                    ->columns(1),
            ]);
    }
}
