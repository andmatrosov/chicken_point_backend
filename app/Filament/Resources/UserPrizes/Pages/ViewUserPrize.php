<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Actions\CancelUserPrizeAction as CancelUserPrizeDomainAction;
use App\Actions\MarkUserPrizeIssuedAction;
use App\Enums\UserPrizeStatus;
use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewUserPrize extends ViewRecord
{
    protected static string $resource = UserPrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markIssued')
                ->label('Отметить как выданный')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Отметить приз как выданный?')
                ->modalDescription('Статус назначения будет обновлен на "выдан", резерв призов не изменится.')
                ->modalSubmitActionLabel('Отметить как выданный')
                ->visible(fn (): bool => $this->getRecord()->status === UserPrizeStatus::PENDING)
                ->action(function (MarkUserPrizeIssuedAction $markUserPrizeIssuedAction): void {
                    try {
                        $markUserPrizeIssuedAction($this->getRecord(), auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Приз отмечен как выданный')
                            ->body('Статус назначения обновлен, действие записано в журнал.')
                            ->send();

                        $this->redirect(UserPrizeResource::getUrl('view', ['record' => $this->getRecord()]));
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
                ->visible(fn (): bool => $this->getRecord()->status === UserPrizeStatus::PENDING)
                ->action(function (CancelUserPrizeDomainAction $cancelUserPrizeAction): void {
                    try {
                        $cancelUserPrizeAction($this->getRecord(), auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Назначение приза отменено')
                            ->body('Назначение отменено, действие записано в журнал.')
                            ->send();

                        $this->redirect(UserPrizeResource::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Не удалось отменить назначение')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
