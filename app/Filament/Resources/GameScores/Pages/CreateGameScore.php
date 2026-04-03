<?php

namespace App\Filament\Resources\GameScores\Pages;

use App\Filament\Resources\GameScores\GameScoreResource;
use Filament\Resources\Pages\CreateRecord;

class CreateGameScore extends CreateRecord
{
    protected static string $resource = GameScoreResource::class;
}
