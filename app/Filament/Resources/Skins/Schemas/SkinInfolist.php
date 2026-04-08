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
                Section::make('Данные скина')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('title')
                            ->label('Название'),
                        TextEntry::make('code')
                            ->label('Код'),
                        TextEntry::make('price')
                            ->label('Цена'),
                        TextEntry::make('image')
                            ->label('Изображение')
                            ->placeholder('Изображение отсутствует'),
                        TextEntry::make('sort_order')
                            ->label('Порядок сортировки')
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
