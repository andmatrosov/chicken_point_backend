<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditGameSession extends EditRecord
{
    protected static string $resource = GameSessionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
