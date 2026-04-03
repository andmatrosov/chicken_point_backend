<?php

namespace App\Filament\Resources\Skins\Pages;

use App\Filament\Resources\Skins\SkinResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSkins extends ListRecords
{
    protected static string $resource = SkinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
