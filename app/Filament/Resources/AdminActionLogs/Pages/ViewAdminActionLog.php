<?php

namespace App\Filament\Resources\AdminActionLogs\Pages;

use App\Filament\Resources\AdminActionLogs\AdminActionLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAdminActionLog extends ViewRecord
{
    protected static string $resource = AdminActionLogResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
