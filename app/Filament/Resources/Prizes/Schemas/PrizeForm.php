<?php

namespace App\Filament\Resources\Prizes\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PrizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Prize')
                    ->schema([
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->rows(3),
                        TextInput::make('quantity')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('default_rank_from')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('default_rank_to')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('is_active')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
