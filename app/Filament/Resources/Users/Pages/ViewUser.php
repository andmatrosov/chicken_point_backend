<?php

namespace App\Filament\Resources\Users\Pages;

use App\Actions\AssignPrizeManuallyAction;
use App\Filament\Resources\Users\UserResource;
use App\Models\Prize;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Auth\Access\AuthorizationException;
use Throwable;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('assignPrizeManually')
                ->label('Assign Prize')
                ->icon('heroicon-o-gift')
                ->form([
                    Select::make('prize_id')
                        ->label('Prize')
                        ->options(
                            fn (): array => Prize::query()
                                ->where('is_active', true)
                                ->orderBy('title')
                                ->pluck('title', 'id')
                                ->all(),
                        )
                        ->searchable()
                        ->required(),
                    TextInput::make('rank_at_assignment')
                        ->label('Rank at assignment')
                        ->numeric()
                        ->minValue(1),
                ])
                ->action(function (array $data, AssignPrizeManuallyAction $assignPrizeManuallyAction): void {
                    /** @var User $admin */
                    $admin = auth()->user();

                    try {
                        $prize = Prize::query()->findOrFail((int) $data['prize_id']);

                        $assignPrizeManuallyAction(
                            $this->record,
                            $prize,
                            $admin,
                            filled($data['rank_at_assignment'] ?? null) ? (int) $data['rank_at_assignment'] : null,
                        );

                        Notification::make()
                            ->success()
                            ->title('Prize assigned')
                            ->body('The prize was assigned successfully.')
                            ->send();
                    } catch (AuthorizationException|Throwable $exception) {
                        Notification::make()
                            ->danger()
                            ->title('Prize assignment failed')
                            ->body($exception->getMessage())
                            ->send();
                    }
                }),
            EditAction::make(),
        ];
    }
}
