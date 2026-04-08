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
                Section::make('Журнал действий')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('adminUser.email')
                            ->label('Администратор'),
                        TextEntry::make('action')
                            ->label('Действие'),
                        TextEntry::make('entity_type')
                            ->label('Тип сущности'),
                        TextEntry::make('entity_id')
                            ->label('ID сущности'),
                        KeyValueEntry::make('payload')
                            ->label('Данные')
                            ->placeholder('Данные отсутствуют'),
                        TextEntry::make('created_at')
                            ->label('Создан')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
