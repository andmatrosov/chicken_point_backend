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
