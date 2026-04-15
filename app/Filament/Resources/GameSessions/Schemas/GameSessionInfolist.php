<?php

namespace App\Filament\Resources\GameSessions\Schemas;

use App\Support\AdminPanelLabel;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GameSessionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Сессия')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('user.email')
                            ->label('Участник'),
                        TextEntry::make('token')
                            ->label('Токен')
                            ->copyable(),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (mixed $state): ?string => AdminPanelLabel::gameSessionStatus($state))
                            ->badge(),
                        TextEntry::make('issued_at')
                            ->label('Выдана')
                            ->dateTime(),
                        TextEntry::make('expires_at')
                            ->label('Истекает')
                            ->dateTime()
                            ->placeholder('Без ограничения по времени'),
                        TextEntry::make('submitted_at')
                            ->label('Отправлена')
                            ->dateTime()
                            ->placeholder('Еще не отправлена'),
                        KeyValueEntry::make('metadata')
                            ->label('Метаданные')
                            ->placeholder('Метаданные отсутствуют'),
                    ])
                    ->columns(2),
            ]);
    }
}
