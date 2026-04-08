<?php

namespace App\Filament\Resources\Prizes\Pages;

use App\Actions\DeletePrizeAction as DeletePrizeDomainAction;
use App\Filament\Resources\Prizes\PrizeResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Throwable;

class ViewPrize extends ViewRecord
{
    protected static string $resource = PrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
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
