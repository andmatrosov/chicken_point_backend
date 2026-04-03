<?php

namespace App\Filament\Resources\AdminActionLogs\Pages;

use App\Filament\Resources\AdminActionLogs\AdminActionLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAdminActionLog extends CreateRecord
{
    protected static string $resource = AdminActionLogResource::class;
}
