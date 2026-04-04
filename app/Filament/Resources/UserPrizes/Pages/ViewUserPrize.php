<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Actions\CancelUserPrizeAction as CancelUserPrizeDomainAction;
use App\Actions\MarkUserPrizeIssuedAction;
use App\Enums\UserPrizeStatus;
use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\ViewRecord;
use Throwable;

class ViewUserPrize extends ViewRecord
{
    protected static string $resource = UserPrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markIssued')
                ->label('Mark issued')
                ->icon(Heroicon::OutlinedCheckBadge)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Mark prize as issued?')
                ->modalDescription('This confirms the assignment has been fulfilled. Reserved stock will not change.')
                ->modalSubmitActionLabel('Mark issued')
                ->visible(fn (): bool => $this->getRecord()->status === UserPrizeStatus::PENDING)
                ->action(function (MarkUserPrizeIssuedAction $markUserPrizeIssuedAction): void {
                    try {
                        $markUserPrizeIssuedAction($this->getRecord(), auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Prize marked as issued')
                            ->body('The assignment status was updated safely and the audit log was recorded.')
                            ->send();

                        $this->redirect(UserPrizeResource::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Issue transition failed')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
            Action::make('cancelAssignment')
                ->label('Cancel assignment')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel prize assignment?')
                ->modalDescription('This cancels a pending assignment, restores reserved stock, and keeps the assignment in history.')
                ->modalSubmitActionLabel('Cancel assignment')
                ->visible(fn (): bool => $this->getRecord()->status === UserPrizeStatus::PENDING)
                ->action(function (CancelUserPrizeDomainAction $cancelUserPrizeAction): void {
                    try {
                        $cancelUserPrizeAction($this->getRecord(), auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Prize assignment canceled')
                            ->body('The assignment was marked as canceled and the audit log was recorded.')
                            ->send();

                        $this->redirect(UserPrizeResource::getUrl('view', ['record' => $this->getRecord()]));
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Cancellation failed')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
