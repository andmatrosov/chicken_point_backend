<?php

namespace App\Filament\Resources\GameSessions\Pages;

use App\Filament\Resources\GameSessions\GameSessionResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameSession extends CreateRecord
{
    protected static string $resource = GameSessionResource::class;
}
