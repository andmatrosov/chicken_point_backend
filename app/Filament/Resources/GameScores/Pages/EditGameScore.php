<?php

namespace App\Filament\Resources\GameScores\Pages;

use App\Filament\Resources\GameScores\GameScoreResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameScore extends EditRecord
{
    protected static string $resource = GameScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
