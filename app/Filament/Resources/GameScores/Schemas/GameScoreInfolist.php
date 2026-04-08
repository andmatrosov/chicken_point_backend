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
                Section::make('Результат')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('user.email')
                            ->label('Участник'),
                        TextEntry::make('score')
                            ->label('Счет'),
                        TextEntry::make('session_token')
                            ->label('Токен сессии'),
                        IconEntry::make('is_processed')
                            ->label('Обработан')
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
