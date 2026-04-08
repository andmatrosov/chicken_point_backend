<?php

namespace App\Filament\Resources\UserPrizes\Schemas;

use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use App\Support\AdminPanelLabel;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserPrizeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Данные назначения')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('user.email')
                            ->label('Участник'),
                        TextEntry::make('prize.title')
                            ->label('Приз')
                            ->formatStateUsing(fn (?string $state, UserPrize $record): ?string => $record->status === UserPrizeStatus::CANCELED ? null : $state)
                            ->placeholder('Активный приз отсутствует'),
                        TextEntry::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (UserPrizeStatus|string|null $state): ?string => AdminPanelLabel::userPrizeStatus($state))
                            ->badge(),
                        TextEntry::make('rank_at_assignment')
                            ->label('Ранг')
                            ->placeholder('Не задан'),
                        IconEntry::make('assigned_manually')
                            ->label('Вручную')
                            ->boolean(),
                        TextEntry::make('assignedBy.email')
                            ->label('Назначил')
                            ->placeholder('Система'),
                        TextEntry::make('assigned_at')
                            ->label('Назначен')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}
