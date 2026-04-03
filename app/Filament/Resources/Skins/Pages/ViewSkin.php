<?php

namespace App\Filament\Resources\Skins\Pages;

use App\Filament\Resources\Skins\SkinResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewSkin extends ViewRecord
{
    protected static string $resource = SkinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
