<?php

namespace App\Filament\Resources\UserPrizes\Schemas;

use App\Enums\UserPrizeStatus;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserPrizeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment')
                    ->schema([
                        TextInput::make('user.email')
                            ->label('User')
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('prize.title')
                            ->label('Prize')
                            ->formatStateUsing(fn (?string $state, ?\App\Models\UserPrize $record): string => $record?->status === UserPrizeStatus::CANCELED ? 'No active prize' : ($state ?? 'No active prize'))
                            ->disabled()
                            ->dehydrated(false),
                        Select::make('status')
                            ->options([
                                UserPrizeStatus::PENDING->value => 'Pending',
                                UserPrizeStatus::ISSUED->value => 'Issued',
                                UserPrizeStatus::CANCELED->value => 'Canceled',
                            ])
                            ->required(),
                        TextInput::make('rank_at_assignment')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}
