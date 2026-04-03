<?php

namespace App\Filament\Resources\Prizes\Pages;

use App\Filament\Resources\Prizes\PrizeResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditPrize extends EditRecord
{
    protected static string $resource = PrizeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
