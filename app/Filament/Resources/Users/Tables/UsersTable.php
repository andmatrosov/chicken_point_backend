<?php

namespace App\Filament\Resources\Users\Tables;

use App\Enums\UserPrizeStatus;
use App\Models\User;
use App\Support\CountryFlagHelper;
use Illuminate\Database\Eloquent\Builder;
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
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        return $query->where(function (Builder $query) use ($search): void {
                            if (self::isIpv6PrefixSearch($search)) {
                                $query->where('registration_ip', 'like', "{$search}%");

                                return;
                            }

                            if (self::isIpSearch($search)) {
                                $query->where('registration_ip', $search);

                                return;
                            }

                            $query->where('email', 'like', "%{$search}%");
                        });
                    })
                    ->sortable(),
                TextColumn::make('country_code')
                    ->label('Country')
                    ->formatStateUsing(fn (?string $state): string => CountryFlagHelper::fromCode($state) ?? '—')
                    ->tooltip(fn (User $record): string => sprintf(
                        "Country: %s\nIP: %s",
                        $record->country_name ?: 'Unknown',
                        $record->registration_ip ?: 'Unknown',
                    ))
                    ->sortable()
                    ->copyable(fn (User $record): bool => filled($record->registration_ip))
                    ->copyableState(fn (User $record): ?string => $record->registration_ip)
                    ->copyMessage('Registration IP copied')
                    ->copyMessageDuration(1500),
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
            ->defaultPaginationPageOption(100)
            ->paginated([100, 250, 500])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }

    private static function isIpSearch(string $search): bool
    {
        return filter_var($search, FILTER_VALIDATE_IP) !== false;
    }

    private static function isIpv6PrefixSearch(string $search): bool
    {
        return str_contains($search, ':') && str_ends_with($search, ':');
    }
}
