<?php

namespace App\Filament\Resources\AdminActionLogs\Schemas;

use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class AdminActionLogInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Action log')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('adminUser.email')
                            ->label('Admin user'),
                        TextEntry::make('action'),
                        TextEntry::make('entity_type'),
                        TextEntry::make('entity_id'),
                        KeyValueEntry::make('payload')
                            ->placeholder('No payload'),
                        TextEntry::make('created_at')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
