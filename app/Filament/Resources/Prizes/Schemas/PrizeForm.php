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
                Section::make('Приз')
                    ->schema([
                        TextInput::make('title')
                            ->label('Название')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Описание')
                            ->rows(3),
                        TextInput::make('quantity')
                            ->label('Количество')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('default_rank_from')
                            ->label('Ранг от')
                            ->numeric()
                            ->minValue(1),
                        TextInput::make('default_rank_to')
                            ->label('Ранг до')
                            ->numeric()
                            ->minValue(1),
                        Toggle::make('is_active')
                            ->label('Активен')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }
}
