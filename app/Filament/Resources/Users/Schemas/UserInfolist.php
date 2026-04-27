<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Actions\GetUserRankAction;
use App\Models\User;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Обзор участника')
                    ->schema([
                        TextEntry::make('id')
                            ->label('ID'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('coins')
                            ->label('Монеты'),
                        TextEntry::make('best_score')
                            ->label('Лучший счет'),
                        TextEntry::make('current_rank')
                            ->label('Текущий ранг')
                            ->state(fn (User $record): ?int => app(GetUserRankAction::class)($record))
                            ->placeholder('Не участвует в лидерборде'),
                        IconEntry::make('has_suspicious_game_results')
                            ->label('Подозрительные результаты')
                            ->boolean(),
                        TextEntry::make('suspicious_game_result_points')
                            ->label('Suspicion points'),
                        TextEntry::make('suspicious_game_results_flagged_at')
                            ->label('Флаг установлен')
                            ->dateTime()
                            ->placeholder('Флаг не установлен'),
                        TextEntry::make('suspicious_game_results_reason')
                            ->label('Причина флага')
                            ->placeholder('Причина не указана'),
                        TextEntry::make('registration_ip')
                            ->label('IP')
                            ->placeholder('Не указано'),
                        TextEntry::make('country_name')
                            ->label('Страна')
                            ->placeholder('Не указано'),
                        TextEntry::make('activeSkin.title')
                            ->label('Активный скин')
                            ->placeholder('Активный скин не выбран'),
                        IconEntry::make('is_admin')
                            ->label('Администратор')
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
