<?php

namespace App\Filament\Resources\Skins\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SkinForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Skin')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('code')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('price')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('image')
                            ->maxLength(255),
                        TextInput::make('sort_order')
                            ->numeric()
                            ->minValue(0),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
