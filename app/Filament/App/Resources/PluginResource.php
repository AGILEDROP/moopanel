<?php

namespace App\Filament\App\Resources;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\Width;
use App\Filament\App\Resources\PluginResource\Pages\ManagePlugins;
use App\Filament\App\Resources\PluginResource\Pages;
use App\Filament\Custom\App as CustomAppComponents;
use App\Models\Instance;
use App\Models\InstancePlugin;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PluginResource extends Resource
{
    protected static ?string $model = InstancePlugin::class;

    protected static string | \BackedEnum | null $navigationIcon = 'fas-plug';

    protected static ?string $tenantOwnershipRelationshipName = 'instance';

    protected static ?string $label = 'Plugins';

    public static function getTableQuery()
    {
        return InstancePlugin::where('instance_id', filament()->getTenant()->id)->with('plugin')->withExists('updates');
    }

    public static function table(Table $table): Table
    {
        $syncType = SyncTypeFactory::create(PluginsSyncType::TYPE, Instance::find(filament()->getTenant()->id));

        return $table
            ->query(fn () => self::getTableQuery())
            ->description($syncType->getLatestTimeText())
            ->columns([
                TextColumn::make('plugin.display_name')
                    ->label(__('Plugin name'))
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Model $record) => $record->plugin->type)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('plugin.component')
                    ->label(__('Component'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('enabled')
                    ->label(__('Enabled'))
                    ->sortable()
                    ->boolean(),
                IconColumn::make('updates_exists')
                    ->label(__('Available updates'))
                    ->icon('fas-plug')
                    ->color(fn ($state) => $state === true ? 'danger' : 'gray')
                    ->sortable()
                    ->boolean(),
                TextColumn::make('version')
                    ->label(__('Version')),
                TextColumn::make('lastUpdateTime')
                    ->label(__('Last update'))
                    ->dateTime(),
            ])
            ->defaultSort('updates_exists', 'desc')
            ->filters([
                TernaryFilter::make('enabled')
                    ->label(__('Enabled'))
                    ->placeholder(__('Select option')),
                CustomAppComponents\Filters\AvailableUpdatesFilter::make('available_updates'),
                CustomAppComponents\Filters\InstancePluginTypeFilter::make('plugin_type'),
            ])
            ->paginated([10, 25, 50])
            ->headerActions([
                $syncType->getTableAction('sync', ['managePluginsPage']),
            ])
            ->recordActions([
                CustomAppComponents\Actions\Table\UpdatePluginAction::make('update_plugin', ['managePluginsPage']),
                CustomAppComponents\Actions\Table\PluginUpdateLogAction::make(),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    // todo: wait for endpoint and implement action logic!
                    //  Tables\Actions\BulkAction::make('enable_plugins')
                    //      ->label(__('Enable'))
                    //      ->icon('heroicon-o-check-circle')
                    //      ->action(fn () => dd('Implement logic when endpoint will be available!')),
                    CustomAppComponents\Actions\Table\UpdatePluginsBulkAction::make('update_plugins', ['managePluginsPage']),
                    DeleteBulkAction::make(),
                ])->dropdownWidth(Width::Medium),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManagePlugins::route('/'),
        ];
    }
}
