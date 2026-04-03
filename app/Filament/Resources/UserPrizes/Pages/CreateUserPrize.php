<?php

namespace App\Filament\Resources\UserPrizes\Pages;

use App\Filament\Resources\UserPrizes\UserPrizeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserPrize extends CreateRecord
{
    protected static string $resource = UserPrizeResource::class;
}
