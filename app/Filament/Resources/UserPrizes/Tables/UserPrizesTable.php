<?php

namespace App\Filament\Resources\UserPrizes\Tables;

use App\Actions\CancelUserPrizeAction as CancelUserPrizeDomainAction;
use App\Actions\MarkUserPrizeIssuedAction;
use App\Enums\UserPrizeStatus;
use App\Models\UserPrize;
use App\Support\AdminPanelLabel;
use Filament\Actions\Action;
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
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Участник')
                    ->searchable(),
                TextColumn::make('prize.title')
                    ->label('Приз')
                    ->formatStateUsing(fn (?string $state, UserPrize $record): ?string => $record->status === UserPrizeStatus::CANCELED ? null : $state)
                    ->placeholder('Активный приз отсутствует')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Статус')
                    ->formatStateUsing(fn (UserPrizeStatus|string|null $state): ?string => AdminPanelLabel::userPrizeStatus($state))
                    ->badge(),
                TextColumn::make('rank_at_assignment')
                    ->label('Ранг'),
                IconColumn::make('assigned_manually')
                    ->label('Вручную')
                    ->boolean(),
                TextColumn::make('assignedBy.email')
                    ->label('Назначил')
                    ->placeholder('Система'),
                TextColumn::make('assigned_at')
                    ->label('Назначен')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('markIssued')
                    ->label('Отметить как выданный')
                    ->icon(Heroicon::OutlinedCheckBadge)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Отметить приз как выданный?')
                    ->modalDescription('Статус назначения будет обновлен на "выдан", резерв призов не изменится.')
                    ->modalSubmitActionLabel('Отметить как выданный')
                    ->visible(fn (UserPrize $record): bool => $record->status === UserPrizeStatus::PENDING)
                    ->action(function (UserPrize $record, MarkUserPrizeIssuedAction $markUserPrizeIssuedAction): void {
                        try {
                            $markUserPrizeIssuedAction($record, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Приз отмечен как выданный')
                                ->body('Статус назначения обновлен, действие записано в журнал.')
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Не удалось обновить статус выдачи')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
                Action::make('cancelAssignment')
                    ->label('Отменить назначение')
                    ->icon(Heroicon::OutlinedXCircle)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Отменить назначение приза?')
                    ->modalDescription('Назначение в статусе ожидания будет отменено, резерв будет восстановлен, запись останется в истории.')
                    ->modalSubmitActionLabel('Отменить назначение')
                    ->visible(fn (UserPrize $record): bool => $record->status === UserPrizeStatus::PENDING)
                    ->action(function (UserPrize $record, CancelUserPrizeDomainAction $cancelUserPrizeAction): void {
                        try {
                            $cancelUserPrizeAction($record, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Назначение приза отменено')
                                ->body('Назначение отменено, действие записано в журнал.')
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Не удалось отменить назначение')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
