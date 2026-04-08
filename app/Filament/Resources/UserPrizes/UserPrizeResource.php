<?php

namespace App\Filament\Resources\UserPrizes;

use App\Filament\Resources\UserPrizes\Pages\ListUserPrizes;
use App\Filament\Resources\UserPrizes\Pages\ViewUserPrize;
use App\Filament\Resources\UserPrizes\Schemas\UserPrizeForm;
use App\Filament\Resources\UserPrizes\Schemas\UserPrizeInfolist;
use App\Filament\Resources\UserPrizes\Tables\UserPrizesTable;
use App\Models\UserPrize;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserPrizeResource extends Resource
{
    protected static ?string $model = UserPrize::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'Операции';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return 'Назначения призов';
    }

    public static function getModelLabel(): string
    {
        return 'назначение приза';
    }

    public static function getPluralModelLabel(): string
    {
        return 'назначения призов';
    }

    public static function form(Schema $schema): Schema
    {
        return UserPrizeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return UserPrizeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UserPrizesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUserPrizes::route('/'),
            'view' => ViewUserPrize::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
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

    public static function canEdit(Model $record): bool
    {
        return false;
    }
}
