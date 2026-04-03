<?php

namespace App\Filament\Resources\GameScores\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GameScoreInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Score')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('user.email')
                            ->label('User'),
                        TextEntry::make('score'),
                        TextEntry::make('session_token'),
                        IconEntry::make('is_processed')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
