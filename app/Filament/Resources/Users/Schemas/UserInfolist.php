<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User overview')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('email'),
                        TextEntry::make('coins'),
                        TextEntry::make('best_score'),
                        TextEntry::make('activeSkin.title')
                            ->label('Active skin')
                            ->placeholder('No active skin'),
                        IconEntry::make('is_admin')
                            ->label('Admin')
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
