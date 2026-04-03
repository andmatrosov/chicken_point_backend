<?php

namespace App\Filament\Resources\Prizes\Pages;

use App\Actions\DeletePrizeAction as DeletePrizeDomainAction;
use App\Filament\Resources\Prizes\PrizeResource;
use App\Services\PrizeRangeValidationService;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Resources\Pages\EditRecord;
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
                ->label('Delete prize')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete prize?')
                ->modalDescription('This will permanently delete the prize and remove all assignments of this prize from users. This action cannot be undone.')
                ->modalSubmitActionLabel('Delete prize')
                ->action(function (DeletePrizeDomainAction $deletePrizeAction): void {
                    try {
                        $deletePrizeAction($this->getRecord(), auth()->user());

                        Notification::make()
                            ->success()
                            ->title('Prize deleted')
                            ->body('The prize and its assignments were removed and the deletion was logged.')
                            ->send();

                        $this->redirect(PrizeResource::getUrl('index'));
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->danger()
                            ->title('Deletion failed')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
        ];
    }
}
