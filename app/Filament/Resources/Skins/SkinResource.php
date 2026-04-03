<?php

namespace App\Filament\Resources\Skins;

use App\Filament\Resources\Skins\Pages\CreateSkin;
use App\Filament\Resources\Skins\Pages\EditSkin;
use App\Filament\Resources\Skins\Pages\ListSkins;
use App\Filament\Resources\Skins\Pages\ViewSkin;
use App\Filament\Resources\Skins\Schemas\SkinForm;
use App\Filament\Resources\Skins\Schemas\SkinInfolist;
use App\Filament\Resources\Skins\Tables\SkinsTable;
use App\Models\Skin;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class SkinResource extends Resource
{
    protected static ?string $model = Skin::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 20;

    public static function form(Schema $schema): Schema
    {
        return SkinForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SkinInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SkinsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSkins::route('/'),
            'create' => CreateSkin::route('/create'),
            'view' => ViewSkin::route('/{record}'),
            'edit' => EditSkin::route('/{record}/edit'),
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
