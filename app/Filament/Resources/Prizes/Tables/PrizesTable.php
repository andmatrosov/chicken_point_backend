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
                    ->sortable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('quantity')
                    ->sortable(),
                TextColumn::make('default_rank_from')
                    ->label('Rank from')
                    ->sortable(),
                TextColumn::make('default_rank_to')
                    ->label('Rank to')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->boolean(),
                TextColumn::make('updated_at')
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
                    ->label('Delete prize')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Delete prize?')
                    ->modalDescription('This will permanently delete the prize and remove all assignments of this prize from users. This action cannot be undone.')
                    ->modalSubmitActionLabel('Delete prize')
                    ->action(function (Prize $record, DeletePrizeDomainAction $deletePrizeAction): void {
                        try {
                            $deletePrizeAction($record, auth()->user());

                            Notification::make()
                                ->success()
                                ->title('Prize deleted')
                                ->body('The prize and its assignments were removed and the deletion was logged.')
                                ->send();
                        } catch (Throwable $exception) {
                            report($exception);

                            Notification::make()
                                ->danger()
                                ->title('Deletion failed')
                                ->body($exception->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->toolbarActions([]);
    }
}
