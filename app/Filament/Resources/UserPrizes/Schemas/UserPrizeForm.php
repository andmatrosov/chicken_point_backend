<?php

namespace App\Filament\Resources\UserPrizes\Schemas;

use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use App\Support\AdminPanelLabel;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserPrizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Назначение')
                    ->schema([
                        TextInput::make('user.email')
                            ->label('Участник')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('prize.title')
                            ->label('Приз')
                            ->formatStateUsing(fn (?string $state, ?UserPrize $record): string => $record?->status === UserPrizeStatus::CANCELED ? 'Активный приз отсутствует' : ($state ?? 'Активный приз отсутствует'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('status')
                            ->label('Статус')
                            ->formatStateUsing(fn (mixed $state): ?string => AdminPanelLabel::userPrizeStatus($state))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('rank_at_assignment')
                            ->label('Ранг')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}
