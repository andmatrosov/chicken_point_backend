<?php

namespace App\Filament\Resources\Prizes\Pages;

use App\Actions\DeletePrizeAction as DeletePrizeDomainAction;
use App\Filament\Resources\Prizes\PrizeResource;
use App\Services\PrizeRangeValidationService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class EditPrize extends EditRecord
{
    protected static string $resource = PrizeResource::class;

    protected function beforeSave(): void
    {
        app(PrizeRangeValidationService::class)->validateForUpsert(
            $this->form->getState(),
            $this->getRecord(),
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            Action::make('deletePrize')
                ->label('Удалить приз')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Удалить приз?')
                ->modalDescription('Приз будет удален безвозвратно вместе со всеми его назначениями. Это действие нельзя отменить.')
                ->modalSubmitActionLabel('Удалить приз')
                ->action(function (DeletePrizeDomainAction $deletePrizeAction): void {
                    try {
                        $deletePrizeAction($this->getRecord(), auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Приз удален')
                            ->body('Приз и связанные назначения удалены, действие записано в журнал.')
                            ->send();

                        $this->redirect(PrizeResource::getUrl('index'));
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Не удалось удалить приз')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
