<?php

namespace App\Filament\Resources\UserPrizes\Schemas;

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
                            ->formatStateUsing(fn (?string $state, ?\App\Models\UserPrize $record): string => $record?->status === \App\Enums\UserPrizeStatus::CANCELED ? 'No active prize' : ($state ?? 'No active prize'))
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('status')
                            ->formatStateUsing(fn ($state): ?string => is_string($state) ? ucfirst($state) : null)
                            ->disabled()
                            ->dehydrated(false),
                        TextInput::make('rank_at_assignment')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2),
            ]);
    }
}
