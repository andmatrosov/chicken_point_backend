<?php

namespace App\Filament\Resources\Skins\Schemas;

use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SkinInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Skin details')
                    ->schema([
                        TextEntry::make('id'),
                        TextEntry::make('title'),
                        TextEntry::make('code'),
                        TextEntry::make('price'),
                        TextEntry::make('image')
                            ->placeholder('No image'),
                        TextEntry::make('sort_order')
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
