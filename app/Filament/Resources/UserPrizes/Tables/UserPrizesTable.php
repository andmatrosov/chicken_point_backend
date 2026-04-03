<?php

namespace App\Filament\Resources\UserPrizes\Tables;

use App\Actions\CancelUserPrizeAction as CancelUserPrizeDomainAction;
use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class UserPrizesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                TextColumn::make('prize.title')
                    ->label('Prize')
                    ->formatStateUsing(fn (?string $state, UserPrize $record): ?string => $record->status === UserPrizeStatus::CANCELED ? null : $state)
                    ->placeholder('No active prize')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('rank_at_assignment')
                    ->label('Rank'),
                IconColumn::make('assigned_manually')
                    ->label('Manual')
                    ->boolean(),
                TextColumn::make('assignedBy.email')
                    ->label('Assigned by')
                    ->placeholder('System'),
                TextColumn::make('assigned_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('cancelAssignment')
                    ->label('Cancel assignment')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Cancel prize assignment?')
                    ->modalDescription('This will mark the assignment as canceled and may restore prize stock. This action keeps the assignment in history.')
                    ->modalSubmitActionLabel('Cancel assignment')
                    ->visible(fn (UserPrize $record): bool => $record->status !== UserPrizeStatus::CANCELED)
                    ->action(function (UserPrize $record, CancelUserPrizeDomainAction $cancelUserPrizeAction): void {
                        try {
                            $cancelUserPrizeAction($record, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Prize assignment canceled')
                                ->body('The assignment was marked as canceled and the audit log was recorded.')
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Cancellation failed')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
