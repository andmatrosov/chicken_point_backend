<?php

namespace App\Filament\Resources\MvpSettings\Pages;

use App\Filament\Resources\MvpSettings\MvpSettingResource;
use Filament\Resources\Pages\ListRecords;

class ListMvpSettings extends ListRecords
{
    protected static string $resource = MvpSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
