<?php

namespace App\Filament\Resources\GameScores\Pages;

use App\Filament\Resources\GameScores\GameScoreResource;
use Filament\Resources\Pages\ListRecords;

class ListGameScores extends ListRecords
{
    protected static string $resource = GameScoreResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
