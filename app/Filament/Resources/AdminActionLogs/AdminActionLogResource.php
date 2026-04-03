<?php

namespace App\Filament\Resources\AdminActionLogs;

use App\Filament\Resources\AdminActionLogs\Pages\ListAdminActionLogs;
use App\Filament\Resources\AdminActionLogs\Pages\ViewAdminActionLog;
use App\Filament\Resources\AdminActionLogs\Schemas\AdminActionLogInfolist;
use App\Filament\Resources\AdminActionLogs\Tables\AdminActionLogsTable;
use App\Models\AdminActionLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdminActionLogResource extends Resource
{
    protected static ?string $model = AdminActionLog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 80;

    public static function infolist(Schema $schema): Schema
    {
        return AdminActionLogInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminActionLogsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminActionLogs::route('/'),
            'view' => ViewAdminActionLog::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }
}
