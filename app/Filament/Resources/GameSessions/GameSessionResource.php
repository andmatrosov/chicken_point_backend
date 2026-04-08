<?php

namespace App\Filament\Resources\GameSessions;

use App\Filament\Resources\GameSessions\Pages\ListGameSessions;
use App\Filament\Resources\GameSessions\Pages\ViewGameSession;
use App\Filament\Resources\GameSessions\Schemas\GameSessionInfolist;
use App\Filament\Resources\GameSessions\Tables\GameSessionsTable;
use App\Models\GameSession;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GameSessionResource extends Resource
{
    protected static ?string $model = GameSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Мониторинг';

    protected static ?int $navigationSort = 60;

    public static function getNavigationLabel(): string
    {
        return 'Игровые сессии';
    }

    public static function getModelLabel(): string
    {
        return 'игровая сессия';
    }

    public static function getPluralModelLabel(): string
    {
        return 'игровые сессии';
    }

    public static function infolist(Schema $schema): Schema
    {
        return GameSessionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameSessionsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameSessions::route('/'),
            'view' => ViewGameSession::route('/{record}'),
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
