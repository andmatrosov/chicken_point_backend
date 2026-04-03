<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GameSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Session')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.email')
                            ->label('User'),
                        TextEntry::make('token')
                            ->copyable(),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('issued_at')
                            ->dateTime(),
                        TextEntry::make('expires_at')
                            ->dateTime(),
                        TextEntry::make('submitted_at')
                            ->dateTime()
                            ->placeholder('Not submitted'),
                        KeyValueEntry::make('metadata')
                            ->placeholder('No metadata'),
                    ])
                    ->columns(2),
            ]);
    }
}
