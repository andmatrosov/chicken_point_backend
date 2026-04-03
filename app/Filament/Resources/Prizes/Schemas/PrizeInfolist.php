<?php

namespace App\Filament\Resources\Prizes\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrizeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prize details')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('title'),
                        TextEntry::make('description')
                            ->placeholder('No description'),
                        TextEntry::make('quantity'),
                        TextEntry::make('default_rank_from')
                            ->placeholder('Not set'),
                        TextEntry::make('default_rank_to')
                            ->placeholder('Not set'),
                        IconEntry::make('is_active')
                            ->boolean(),
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
