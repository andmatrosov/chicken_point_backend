<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Resources\Pages\ListRecords;

class ListUserPrizes extends ListRecords
{
    protected static string $resource = UserPrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
