<?php

namespace App\Filament\Clusters\Updates\Resources;

use App\Filament\Clusters\Updates;
use App\Filament\Clusters\Updates\Resources\PluginsResource\Pages;
use App\Models\Plugin;
use App\Models\Sync;
use App\Services\ModuleApiService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class PluginsResource extends Resource
{
    protected static ?string $model = Plugin::class;

    protected static ?string $navigationIcon = 'fas-layer-group';

    protected static ?string $cluster = Updates::class;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        return $query->where('available_updates', true);
    }

    public static function getRecordsCount(): int
    {
        return static::getEloquentQuery()->count();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getRecordsCount();
    }

    public static function getTableDescription(array $instanceIds = []): string|Htmlable|null
    {
        $lastSync = __('never');
        if (Sync::where('syncable_type', Plugin::class)->whereIn('instance_id', $instanceIds)->orWhereNull('instance_id')->exists()) {
            $lastSync = Sync::where('syncable_type', Plugin::class)->whereIn('instance_id', $instanceIds)->orWhereNull('instance_id')->latest('synced_at')->first()->synced_at->diffForHumans();
        }

        return __('Last sync: ').$lastSync;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading(__('Plugins'))
            ->description(self::getTableDescription())
            ->columns([
                self::getNameColumn(),
                self::getDisplayNameColumn(),
                self::getInstanceNameColumn(),
                self::getTypeColumn(),
                self::getVersionColumn(),
                self::getEnabledColumn(),
                self::getAvailableUpldatesColumn(),
                self::getIsStandardColumn(),
                self::getSettingsSectionColumn(),
                self::getDirectoryColumn(),
            ])
            ->filters([
                self::getInstanceFilter(),
                self::getTypeFilter(),
                self::getEnabledFilter(),
                self::getIsStandardFilter(),
            ])
            ->actions([
                //
            ])
            ->headerActions([
                Tables\Actions\Action::make('sync_plugins')
                    ->label(__('Sync'))
                    ->icon('heroicon-o-arrow-path')
                    // @todo: use job/event for this!
                    ->action(fn () => (new ModuleApiService)->syncAllPlugins())
                    ->after(fn ($livewire) => $livewire->dispatch('updateManagePluginsPage')),
            ])
            ->bulkActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePlugins::route('/'),
        ];
    }

    public static function getNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('name')
            ->label(__('Name'))
            ->searchable()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function getDisplayNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('display_name')
            ->label(__('Display name'))
            ->searchable()
            ->sortable();
    }

    public static function getInstanceNameColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('instance.site_name')
            ->label(__('Instance'))
            ->searchable()
            ->sortable();
    }

    public static function getTypeColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('type')
            ->label(__('Type'))
            ->sortable()
            ->badge();
    }

    public static function getVersionColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('version')
            ->label(__('Version'))
            ->toggleable();
    }

    public static function getEnabledColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('enabled')
            ->label(__('Enabled'))
            ->sortable()
            ->boolean();
    }

    public static function getAvailableUpldatesColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('available_updates')
            ->label(__('Available updates'))
            ->sortable()
            ->boolean();
    }

    public static function getIsStandardColumn(): Tables\Columns\IconColumn
    {
        return Tables\Columns\IconColumn::make('is_standard')
            ->label(__('Standard'))
            ->boolean()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function getSettingsSectionColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('settings_section')
            ->label(__('Settings section'))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function getDirectoryColumn(): Tables\Columns\TextColumn
    {
        return Tables\Columns\TextColumn::make('directory')
            ->label(__('Directory'))
            ->toggleable(isToggledHiddenByDefault: true);
    }

    public static function getInstanceFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('instance_id')
            ->relationship('instance', 'site_name')
            ->label(__('Instance'))
            ->searchable();
    }

    public static function getTypeFilter(): Tables\Filters\SelectFilter
    {
        return Tables\Filters\SelectFilter::make('type')
            ->options(fn () => Plugin::distinct('type')->pluck('type', 'type'))
            ->label(__('Type'))
            ->searchable();
    }

    public static function getEnabledFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('enabled')
            ->label(__('Enabled'));
    }

    public static function getIsStandardFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('is_standard')
            ->label(__('Is standard'));
    }

    public static function getAvailableUpdatesFilter(): Tables\Filters\TernaryFilter
    {
        return Tables\Filters\TernaryFilter::make('available_updates')
            ->label(__('Available updates'));
    }
}
