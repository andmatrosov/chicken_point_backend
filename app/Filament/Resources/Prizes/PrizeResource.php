<?php

namespace App\Filament\Resources\Prizes;

use App\Filament\Resources\Prizes\Pages\CreatePrize;
use App\Filament\Resources\Prizes\Pages\EditPrize;
use App\Filament\Resources\Prizes\Pages\ListPrizes;
use App\Filament\Resources\Prizes\Pages\ViewPrize;
use App\Filament\Resources\Prizes\RelationManagers\UserPrizesRelationManager;
use App\Filament\Resources\Prizes\Schemas\PrizeForm;
use App\Filament\Resources\Prizes\Schemas\PrizeInfolist;
use App\Filament\Resources\Prizes\Tables\PrizesTable;
use App\Models\Prize;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PrizeResource extends Resource
{
    protected static ?string $model = Prize::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 30;

    public static function form(Schema $schema): Schema
    {
        return PrizeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PrizeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PrizesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            UserPrizesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrizes::route('/'),
            'create' => CreatePrize::route('/create'),
            'view' => ViewPrize::route('/{record}'),
            'edit' => EditPrize::route('/{record}/edit'),
        ];
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
