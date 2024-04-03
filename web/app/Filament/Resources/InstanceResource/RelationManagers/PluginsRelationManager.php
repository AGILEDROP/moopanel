<?php

namespace App\Filament\Resources\InstanceResource\RelationManagers;

use App\Filament\Clusters\Updates\Resources\PluginsResource;
use App\Services\ModuleApiService;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PluginsRelationManager extends RelationManager
{
    protected $listeners = ['updateInstancePlugins' => '$refresh'];

    protected static string $relationship = 'plugins';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('display_name')
            ->description(PluginsResource::getTableDescription([$this->ownerRecord->id]))
            ->columns([
                PluginsResource::getNameColumn(),
                PluginsResource::getDisplayNameColumn(),
                PluginsResource::getTypeColumn(),
                PluginsResource::getVersionColumn(),
                PluginsResource::getEnabledColumn(),
                PluginsResource::getAvailableUpldatesColumn(),
                PluginsResource::getIsStandardColumn(),
                PluginsResource::getSettingsSectionColumn(),
                PluginsResource::getDirectoryColumn(),
            ])
            ->filters([
                PluginsResource::getTypeFilter(),
                PluginsResource::getEnabledFilter()
                    ->default(true),
                PluginsResource::getAvailableUpdatesFilter(),
                PluginsResource::getIsStandardFilter(),
            ])
            ->defaultSort('available_updates', 'desc')
            ->headerActions([
                Tables\Actions\Action::make('sync_plugins')
                    ->label('Sync')
                    ->icon('heroicon-o-arrow-path')
                    ->action(fn () => (new ModuleApiService)->syncInstancePlugins($this->ownerRecord))
                    ->after(fn () => $this->dispatch('updateInstancePlugins')),
            ])
            ->actions([
                //
            ])
            ->bulkActions([
                //
            ]);
    }

    public function isReadOnly(): bool
    {
        return false;
    }
}
