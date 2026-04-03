<?php

namespace App\Filament\Resources\AdminActionLogs\Pages;

use App\Filament\Resources\AdminActionLogs\AdminActionLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAdminActionLog extends EditRecord
{
    protected static string $resource = AdminActionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
