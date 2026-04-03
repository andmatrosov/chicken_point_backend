<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUserPrize extends EditRecord
{
    protected static string $resource = UserPrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
