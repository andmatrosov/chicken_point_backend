<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Actions\CancelUserPrizeAction as CancelUserPrizeDomainAction;
use App\Enums\UserPrizeStatus;
use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
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
            EditAction::make(),
            Action::make('cancelAssignment')
                ->label('Cancel assignment')
                ->icon(Heroicon::OutlinedXCircle)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Cancel prize assignment?')
                ->modalDescription('This will mark the assignment as canceled and may restore prize stock. This action keeps the assignment in history.')
                ->modalSubmitActionLabel('Cancel assignment')
                ->visible(fn (): bool => $this->getRecord()->status !== UserPrizeStatus::CANCELED)
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
