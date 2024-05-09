<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\SyncInstanceCoreUpdates;
use App\Jobs\ModuleApi\SyncInstanceInfo;
use App\Jobs\ModuleApi\SyncInstancePlugins;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
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
            $jobs[] = new SyncInstanceInfo($instance);
            $jobs[] = new SyncInstanceCoreUpdates($instance);
            $jobs[] = new SyncInstancePlugins($instance);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}
