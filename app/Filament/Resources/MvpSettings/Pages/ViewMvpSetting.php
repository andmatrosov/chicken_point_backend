<?php

namespace App\Filament\Resources\MvpSettings\Pages;

use App\Filament\Resources\MvpSettings\MvpSettingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMvpSetting extends ViewRecord
{
    protected static string $resource = MvpSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
