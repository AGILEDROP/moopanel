<?php

namespace App\FilamentLogger\Resources;

use App\FilamentLogger\Resources\ActivityResource\Pages\ListActivities;
use App\FilamentLogger\Resources\ActivityResource\Pages\ViewActivity;
use App\FilamentLogger\Resources\ActivityResource\Schemas\ActivityInfolist;
use App\FilamentLogger\Resources\ActivityResource\Tables\ActivitiesTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\ActivitylogServiceProvider;
use UnitEnum;

class ActivityResource extends Resource
{
    protected static ?string $label = 'Activity Log';

    protected static ?string $slug = 'activity-logs';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getCluster(): ?string
    {
        return config('filament-logger.resources.cluster');
    }

    public static function infolist(Schema $schema): Schema
    {
        return ActivityInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActivitiesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActivities::route('/'),
            'view' => ViewActivity::route('/{record}'),
        ];
    }

    public static function getModel(): string
    {
        return ActivitylogServiceProvider::determineActivityModel();
    }

    public static function getLabel(): string
    {
        return __('filament-logger.resource.label.log');
    }

    public static function getPluralLabel(): string
    {
        return __('filament-logger.resource.label.logs');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        $group = config('filament-logger.resources.navigation_group', 'Settings');

        if ($group instanceof UnitEnum) {
            return $group;
        }

        return __($group);
    }

    public static function getNavigationLabel(): string
    {
        return __('filament-logger.nav.log.label');
    }

    public static function getNavigationIcon(): string
    {
        return __('filament-logger.nav.log.icon');
    }

    public static function isScopedToTenant(): bool
    {
        return config('filament-logger.scoped_to_tenant', true);
    }

    public static function getNavigationSort(): ?int
    {
        return config('filament-logger.navigation_sort', null);
    }
}
