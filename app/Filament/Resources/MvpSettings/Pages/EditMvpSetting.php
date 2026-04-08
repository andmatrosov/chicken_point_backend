<?php

namespace App\Filament\Resources\MvpSettings\Pages;

use App\Filament\Resources\MvpSettings\MvpSettingResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMvpSetting extends EditRecord
{
    protected static string $resource = MvpSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
        ];
    }
}
