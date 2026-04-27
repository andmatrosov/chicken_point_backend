<?php

namespace App\Filament\Resources\Users\RelationManagers;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class RelatedProfilesRelationManager extends RelationManager
{
    protected static string $relationship = 'relatedProfilesByRegistrationIpRelation';

    protected static ?string $title = 'Связанные профили';

    protected static bool $isLazy = false;

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User
            && $ownerRecord->hasRelatedProfilesByRegistrationIp();
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->copyable()
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
                TextColumn::make('registration_ip')
                    ->label('IP')
                    ->copyable(),
                TextColumn::make('country_name')
                    ->label('Страна')
                    ->placeholder('Не указано')
                    ->sortable(),
                TextColumn::make('best_score')
                    ->label('Лучший счет')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('has_suspicious_game_results')
                    ->label('Подозрительные результаты')
                    ->boolean(),
                TextColumn::make('suspicious_game_result_points')
                    ->label('Points')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Создан')
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordUrl(fn (User $record): string => UserResource::getUrl('view', ['record' => $record]))
            ->recordActions([
                ViewAction::make()
                    ->label('Открыть аккаунт')
                    ->url(fn (User $record): string => UserResource::getUrl('view', ['record' => $record])),
            ])
            ->headerActions([])
            ->toolbarActions([]);
    }
}
