<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserPrizeStatus;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with([
                'activeSkin',
                'currentPrizeAssignment.prize',
            ])->withCount([
                'userPrizes as active_prize_assignments_count' => fn ($query) => $query->whereIn('status', [
                    UserPrizeStatus::PENDING,
                    UserPrizeStatus::ISSUED,
                ]),
            ]))
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('coins')
                    ->sortable(),
                TextColumn::make('best_score')
                    ->sortable(),
                TextColumn::make('activeSkin.title')
                    ->label('Active skin')
                    ->placeholder('No active skin'),
                TextColumn::make('current_prize')
                    ->label('Latest active prize')
                    ->getStateUsing(function (User $record): ?string {
                        $currentPrizeAssignment = $record->currentPrizeAssignment;

                        if ($currentPrizeAssignment === null) {
                            return null;
                        }

                        $title = $currentPrizeAssignment->prize?->title;

                        if ($title === null) {
                            return null;
                        }

                        return $currentPrizeAssignment->status === UserPrizeStatus::PENDING
                            ? "{$title} (pending)"
                            : $title;
                    })
                    ->description(fn (User $record): ?string => ($record->active_prize_assignments_count ?? 0) > 1
                        ? "{$record->active_prize_assignments_count} active assignments"
                        : null)
                    ->placeholder('No active prize')
                    ->wrap(),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
