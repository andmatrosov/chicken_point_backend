<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('User')
                    ->schema([
                        TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->minLength((int) config('game.auth.password_min_length', 8))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Leave blank to keep the current password.'),
                        TextInput::make('coins')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('best_score')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Toggle::make('is_admin')
                            ->label('Admin access'),
                    ])
                    ->columns(2),
            ]);
    }
}
