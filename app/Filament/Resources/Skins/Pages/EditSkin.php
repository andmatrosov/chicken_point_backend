<?php

namespace App\Filament\Resources\Skins\Pages;

use App\Filament\Resources\Skins\SkinResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditSkin extends EditRecord
{
    protected static string $resource = SkinResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
