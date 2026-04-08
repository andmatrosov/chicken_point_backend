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
                Section::make('Данные приза')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('title')
                            ->label('Название'),
                        TextEntry::make('description')
                            ->label('Описание')
                            ->placeholder('Описание отсутствует'),
                        TextEntry::make('quantity')
                            ->label('Количество'),
                        TextEntry::make('default_rank_from')
                            ->label('Ранг от')
                            ->placeholder('Не задано'),
                        TextEntry::make('default_rank_to')
                            ->label('Ранг до')
                            ->placeholder('Не задано'),
                        IconEntry::make('is_active')
                            ->label('Активен')
                            ->boolean(),
                        TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Обновлен')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
