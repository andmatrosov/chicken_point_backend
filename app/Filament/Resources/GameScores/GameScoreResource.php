<?php

namespace App\Filament\Resources\GameScores;

use App\Filament\Resources\GameScores\Pages\ListGameScores;
use App\Filament\Resources\GameScores\Pages\ViewGameScore;
use App\Filament\Resources\GameScores\Schemas\GameScoreInfolist;
use App\Filament\Resources\GameScores\Tables\GameScoresTable;
use App\Models\GameScore;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class GameScoreResource extends Resource
{
    protected static ?string $model = GameScore::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Monitoring';

    protected static ?int $navigationSort = 50;

    public static function infolist(Schema $schema): Schema
    {
        return GameScoreInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GameScoresTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGameScores::route('/'),
            'view' => ViewGameScore::route('/{record}'),
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
