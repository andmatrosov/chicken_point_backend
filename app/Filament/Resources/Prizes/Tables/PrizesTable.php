<?php

namespace App\Filament\Resources\Prizes\Tables;

use App\Actions\DeletePrizeAction as DeletePrizeDomainAction;
use App\Models\Prize;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Throwable;

class PrizesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->label('Название')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Количество')
                    ->sortable(),
                TextColumn::make('default_rank_from')
                    ->label('Ранг от')
                    ->sortable(),
                TextColumn::make('default_rank_to')
                    ->label('Ранг до')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Активен')
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->label('Обновлен')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('deletePrize')
                    ->label('Удалить приз')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Удалить приз?')
                    ->modalDescription('Приз будет удален безвозвратно вместе со всеми его назначениями. Это действие нельзя отменить.')
                    ->modalSubmitActionLabel('Удалить приз')
                    ->action(function (Prize $record, DeletePrizeDomainAction $deletePrizeAction): void {
                        try {
                            $deletePrizeAction($record, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Приз удален')
                                ->body('Приз и связанные назначения удалены, действие записано в журнал.')
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Не удалось удалить приз')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
