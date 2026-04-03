<?php

namespace App\Filament\Resources\Prizes\Pages;

use App\Filament\Resources\Prizes\PrizeResource;
use App\Services\PrizeRangeValidationService;
use Filament\Resources\Pages\CreateRecord;

class CreatePrize extends CreateRecord
{
    protected static string $resource = PrizeResource::class;

    protected function beforeCreate(): void
    {
        app(PrizeRangeValidationService::class)->validateForUpsert($this->form->getState());
    }
}
