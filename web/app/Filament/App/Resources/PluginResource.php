<?php

namespace App\Filament\App\Resources;

use App\Filament\App\Resources\PluginResource\Pages;
use App\Filament\Custom;
use App\Livewire\Plugin\UpdateLogTable;
use App\Models\Instance;
use App\Models\InstancePlugin;
use App\Models\Plugin;
use App\Models\Sync;
use App\Services\ModuleApiService;
use Filament\Infolists\Components\Livewire;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class PluginResource extends Resource
{
    protected static ?string $model = InstancePlugin::class;

    protected static ?string $navigationIcon = 'fas-plug';

    protected static ?string $tenantOwnershipRelationshipName = 'instance';

    protected static ?string $label = 'Plugins';

    public static function getTableDescription(): string|Htmlable|null
    {
        $time = __('never');
        if (isset(filament()->getTenant()->id)) {
            $lastSync = Sync::where([
                ['instance_id', '=', filament()->getTenant()->id],
                ['type', Plugin::class],
                ['subtype', null],
            ]);
            if ($lastSync->exists()) {
                $time = $lastSync
                    ->latest('synced_at')
                    ->first()
                    ->synced_at
                    ->diffForHumans();
            }
        }

        return __('Last sync: ').$time;
    }

    public static function getTableHeaderActions(): array
    {
        return [
            Tables\Actions\Action::make('sync_plugins')
                ->label('Sync')
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => (new ModuleApiService)->syncInstancePlugins(Instance::find(filament()->getTenant()->id)))
                ->after(fn ($livewire) => $livewire->dispatch('managePluginsPage')),
        ];
    }

    public static function getTableQuery()
    {
        return InstancePlugin::where('instance_id', filament()->getTenant()->id)->with('plugin')->withExists('updates');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => self::getTableQuery())
            ->description(self::getTableDescription())
            ->columns([
                Tables\Columns\TextColumn::make('plugin.display_name')
                    ->label(__('Plugin name'))
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Model $record) => $record->plugin->type)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plugin.component')
                    ->label(__('Component'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('enabled')
                    ->label(__('Enabled'))
                    ->sortable()
                    ->boolean(),
                Tables\Columns\IconColumn::make('updates_exists')
                    ->label(__('Available updates'))
                    ->icon('fas-plug')
                    ->color(fn ($state) => $state === true ? 'danger' : 'gray')
                    ->sortable()
                    ->boolean(),
                Tables\Columns\TextColumn::make('version')
                    ->label(__('Version')),
                Tables\Columns\TextColumn::make('lastUpdateTime')
                    ->label(__('Last update'))
                    ->dateTime(),
            ])
            ->defaultSort('updates_exists', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('enabled')
                    ->label(__('Enabled'))
                    ->placeholder(__('Select option')),
                Custom\App\Filters\AvailableUpdatesFilter::make('available_updates'),
                Custom\App\Filters\InstancePluginTypeFilter::make('plugin_type'),
            ])
            ->headerActions(self::getTableHeaderActions())
            ->actions([
                Custom\App\Actions\Table\UpdatePluginAction::make('update_plugin'),
                Action::make('plugin_log')
                    ->iconButton()
                    ->visible(fn (Model $record): bool => $record->updateLog()->count() > 0)
                    // One way to show custom livewire table inside modal window. You can also use modalContent,
                    // but then there you need two views for same table.
                    ->infolist(fn (Model $record) => [
                        Livewire::make(UpdateLogTable::class, ['plugin' => $record->plugin])
                            ->key('plugin-log-'.$record->id)
                            ->id('plugin-log-'.$record->id),
                    ])
                    ->modalHeading(fn (Model $record) => __('Plugin log: :plugin', ['plugin' => $record->plugin->display_name]))
                    ->modalCancelAction(false)
                    ->modalSubmitAction(false)
                    ->slideOver()
                    ->icon('fas-timeline')
                    ->color('gray'),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('enable_plugins')
                        ->label(__('Enable'))
                        ->icon('heroicon-o-check-circle')
                        ->action(fn () => dd('Implement logic when endpoint will be available!')),
                    Custom\App\Actions\Table\UpdatePluginsBulkAction::make('update_plugins'),
                    Tables\Actions\DeleteBulkAction::make(),
                ])->dropdownWidth(MaxWidth::Medium),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePlugins::route('/'),
        ];
    }
}
