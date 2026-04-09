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
                Section::make('Участник')
                    ->schema([
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->mutateStateForValidationUsing(
                                fn (?string $state): ?string => self::normalizeEmail($state),
                            )
                            ->dehydrateStateUsing(
                                fn (?string $state): ?string => self::normalizeEmail($state),
                            )
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->revealable()
                            ->minLength((int) config('game.auth.password_min_length', 8))
                            ->dehydrated(fn (?string $state): bool => filled($state))
                            ->helperText('Оставьте поле пустым, чтобы не менять текущий пароль.'),
                        TextInput::make('coins')
                            ->label('Монеты')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        TextInput::make('best_score')
                            ->label('Лучший счет')
                            ->numeric()
                            ->required()
                            ->minValue(0),
                        Toggle::make('is_admin')
                            ->label('Доступ администратора'),
                    ])
                    ->columns(2),
            ]);
    }

    protected static function normalizeEmail(?string $state): ?string
    {
        if (! is_string($state)) {
            return $state;
        }

        $normalized = mb_strtolower(trim($state));

        return $normalized !== '' ? $normalized : null;
    }
}
