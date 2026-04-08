<?php

namespace App\Filament\Resources\MvpSettings;

use App\Filament\Resources\MvpSettings\Pages\EditMvpSetting;
use App\Filament\Resources\MvpSettings\Pages\ListMvpSettings;
use App\Filament\Resources\MvpSettings\Pages\ViewMvpSetting;
use App\Filament\Resources\MvpSettings\Schemas\MvpSettingForm;
use App\Filament\Resources\MvpSettings\Schemas\MvpSettingInfolist;
use App\Filament\Resources\MvpSettings\Tables\MvpSettingsTable;
use App\Models\MvpSetting;
use App\Services\MvpSettingService;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MvpSettingResource extends Resource
{
    protected static ?string $model = MvpSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Администрирование';

    protected static ?int $navigationSort = 40;

    public static function getNavigationLabel(): string
    {
        return 'MVP настройки';
    }

    public static function getModelLabel(): string
    {
        return 'MVP настройка';
    }

    public static function getPluralModelLabel(): string
    {
        return 'MVP настройки';
    }

    public static function form(Schema $schema): Schema
    {
        return MvpSettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MvpSettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MvpSettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMvpSettings::route('/'),
            'view' => ViewMvpSetting::route('/{record}'),
            'edit' => EditMvpSetting::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        /** @var MvpSettingService $mvpSettingService */
        $mvpSettingService = app(MvpSettingService::class);

        if (! $mvpSettingService->hasTable()) {
            return $mvpSettingService->getSafeEmptyQuery();
        }

        $mvpSettingService->ensureDefaults();

        return parent::getEloquentQuery()
            ->orderByRaw("case version when 'main' then 0 when 'brazil' then 1 else 99 end");
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
}
