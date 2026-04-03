<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserPrize extends ViewRecord
{
    protected static string $resource = UserPrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
