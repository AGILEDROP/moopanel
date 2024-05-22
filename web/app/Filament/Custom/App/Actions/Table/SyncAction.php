<?php

namespace App\Filament\Custom\App\Actions\Table;

use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Sync;
use App\Models\Update;
use App\Models\UpdateLog;
use App\Services\ModuleApiService;
use Filament\Tables\Actions\Action;
use InvalidArgumentException;

class SyncAction
{
    public static function make(string $name, string $syncType, string $refreshComponent): Action
    {
        return Action::make($name)
            ->label(__('Sync'))
            ->icon('heroicon-o-arrow-path')
            ->action(fn () => self::triggerSync($syncType))
            ->after(fn ($livewire) => $livewire->dispatch($refreshComponent));
    }

    public static function triggerSync(string $syncType): void
    {
        $moduleApiService = new ModuleApiService();

        switch ($syncType) {
            case 'core-update':
            case 'core-update-log':
                $moduleApiService->syncInstanceCoreUpdates(Instance::find(filament()->getTenant()->id), true);
                break;
            case 'plugin-update':
            case 'plugin-update-log':
                $moduleApiService->syncInstancePlugins(Instance::find(filament()->getTenant()->id), true);
                break;
            case 'update-log':
                $moduleApiService->syncInstanceCoreUpdates(Instance::find(filament()->getTenant()->id), true);
                $moduleApiService->syncInstancePlugins(Instance::find(filament()->getTenant()->id), true);
                break;
            default:
                throw new InvalidArgumentException("Invalid sync type: {$syncType}");
        }
    }

    public static function getLastSyncTime(string $syncType): string
    {
        $time = __('never');
        if (isset(filament()->getTenant()->id)) {
            switch ($syncType) {
                case 'core-update':
                    $lastSync = Sync::where([
                        ['instance_id', '=', filament()->getTenant()->id],
                        ['type', '=', Update::class],
                        ['subtype', '=', Instance::class],
                    ]);
                    if ($lastSync->exists()) {
                        $time = $lastSync->latest('synced_at')->first()->synced_at->diffForHumans();
                    }
                    break;
                case 'core-update-log':
                    $lastSync = Sync::where([
                        ['instance_id', '=', filament()->getTenant()->id],
                        ['type', '=', UpdateLog::class],
                        ['subtype', '=', Instance::class],
                    ]);
                    if ($lastSync->exists()) {
                        $time = $lastSync->latest('synced_at')->first()->synced_at->diffForHumans();
                    }
                    break;
                case 'plugin-update':
                    $lastSync = Sync::where([['instance_id', '=', filament()->getTenant()->id], ['type', Update::class], ['subtype', Plugin::class]]);
                    if ($lastSync->exists()) {
                        $time = $lastSync
                            ->latest('synced_at')
                            ->first()
                            ->synced_at
                            ->diffForHumans();
                    }
                    break;
                case 'plugin-update-log':
                    $lastSync = Sync::where([['instance_id', '=', filament()->getTenant()->id], ['type', UpdateLog::class], ['subtype', Plugin::class]]);
                    if ($lastSync->exists()) {
                        $time = $lastSync
                            ->latest('synced_at')
                            ->first()
                            ->synced_at
                            ->diffForHumans();
                    }
                    break;
                case 'update-log':
                    $lastCoreLogSync = Sync::where([['instance_id', '=', filament()->getTenant()->id], ['type', '=', UpdateLog::class], ['subtype', '=', Instance::class]]);
                    $lastPluginsLogSync = Sync::where([['instance_id', '=', filament()->getTenant()->id], ['type', '=', UpdateLog::class], ['subtype', '=', Plugin::class]]);
                    if ($lastCoreLogSync->exists() && $lastPluginsLogSync->exists()) {
                        $lastCoreLogSyncTime = $lastCoreLogSync->latest('synced_at')->first()->synced_at;
                        $lastPluginsLogSyncTime = $lastPluginsLogSync->latest('synced_at')->first()->synced_at;
                        $time = ($lastCoreLogSyncTime < $lastPluginsLogSyncTime) ? $lastCoreLogSyncTime->diffForHumans() : $lastPluginsLogSyncTime->diffForHumans();
                    }
                    break;
                default:
                    throw new InvalidArgumentException("Invalid sync type: {$syncType}");
            }
        }

        return __('Last sync: ').$time;
    }
}
