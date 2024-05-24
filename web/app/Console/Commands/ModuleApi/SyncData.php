<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SingleInstance\InfoSyncType;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncData extends Command
{
    protected $signature = 'module-api:sync-data';

    protected $description = 'Sync all existing instance data (including updates, plugins, log, info, etc.)';

    public function handle(): int
    {
        $jobs = [];
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();
        foreach ($instances as $instance) {
            $jobs[] = new Sync($instance, InfoSyncType::TYPE, 'Instance information sync failed.');
            $jobs[] = new Sync($instance, CoreSyncType::TYPE, 'Core updates sync failed.');
            $jobs[] = new Sync($instance, PluginsSyncType::TYPE, 'Plugin sync failed!');
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}
