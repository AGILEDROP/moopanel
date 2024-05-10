<?php

namespace App\Filament\App\Resources;

use App\Enums\UpdateLogType;
use App\Filament\App\Resources\UpdateLogResource\Pages;
use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Sync;
use App\Models\UpdateLog;
use App\Services\ModuleApiService;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class UpdateLogResource extends Resource
{
    protected static ?string $model = UpdateLog::class;

    protected static ?string $navigationIcon = 'fas-timeline';

    public static function getTableDescription(): string|Htmlable|null
    {
        $lastSyncTime = __('never');
        if (isset(filament()->getTenant()->id)) {
            $lastCoreLogSync = Sync::where([
                ['instance_id', '=', filament()->getTenant()->id],
                ['type', '=', UpdateLog::class],
                ['subtype', '=', Instance::class],
            ]);
            $lastPluginsLogSync = Sync::where([
                ['instance_id', '=', filament()->getTenant()->id],
                ['type', '=', UpdateLog::class],
                ['subtype', '=', Plugin::class],
            ]);
            if ($lastCoreLogSync->exists() && $lastPluginsLogSync->exists()) {
                $lastCoreLogSyncTime = $lastCoreLogSync->latest('synced_at')->first()->synced_at;
                $lastPluginsLogSyncTime = $lastPluginsLogSync->latest('synced_at')->first()->synced_at;
                $lastSyncTime = ($lastCoreLogSyncTime < $lastPluginsLogSyncTime) ? $lastCoreLogSyncTime->diffForHumans() : $lastPluginsLogSyncTime->diffForHumans();
            }
        }

        return __('Last sync: ').$lastSyncTime;
    }

    public static function getTableHeaderActions(): array
    {
        return [
            Action::make('sync_core')
                ->label('Sync')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $moduleApiService = new ModuleApiService();
                    // todo: api provider, should create one route where both plugin and core updates are visible.
                    // todo: Calling two routes for this should be avoided, to improve performance (Ask for new endpoint with complete log).
                    $coreSyncSuccessful = $moduleApiService->syncInstanceCoreUpdates(Instance::find(filament()->getTenant()->id), true);
                    $pluginsSyncSuccessful = $moduleApiService->syncInstancePlugins(Instance::find(filament()->getTenant()->id), true);
                })
                ->after(fn ($livewire) => $livewire->dispatch('manageUpdateLogPage')),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->description(self::getTableDescription())
            ->columns([
                Tables\Columns\TextColumn::make('plugin_exists')
                    ->label('Type')
                    ->exists('plugin')
                    ->formatStateUsing(fn ($state) => $state === true ? __('Plugin') : __('Core'))
                    ->icon(fn ($state) => $state === true ? 'fas-plug' : 'fas-cube')
                    ->color('gray')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('timemodified')
                    ->label(__('Time modified'))
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->label(__('Modified by'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label(__('Version'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Operation result'))
                    ->formatStateUsing(fn (UpdateLogType $state): string => $state->toReadableString())
                    ->color(fn (UpdateLogType $state): string => $state->toDisplayColor())
                    ->icon(fn (UpdateLogType $state): string => $state->toDisplayIcon())
                    ->badge()
                    ->sortable(),
            ])
            ->headerActions(self::getTableHeaderActions())
            ->actions([
                Tables\Actions\Action::make('show_details')
                    ->hiddenLabel()
                    ->icon('heroicon-o-bug-ant')
                    ->hidden(fn (Model $record): bool => $record->details === null)
                    ->modalHeading(__('Details & Backtrace'))
                    // @improve: use infolist for content! -> you can also utilize copy actions, etc.
                    ->modalContent(fn (Model $record) => new HtmlString('<div><b>'.__('Details').':</b><br/>'.$record->details.'</div>
                                            <div><b>'.__('Backtrace').':</b><br/>'.$record->backtrace.'</div>'))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
            ])
            ->defaultSort('timemodified', 'desc')
            ->paginationPageOptions([5, 10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUpdateLogs::route('/'),
        ];
    }
}
