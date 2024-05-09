<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\SyncInstancePlugins;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncPlugins extends Command
{
    protected $signature = 'module-api:sync-plugins';

    protected $description = 'Sync plugins for existing instances.';

    public function handle(): int
    {
        $jobs = [];
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();
        foreach ($instances as $instance) {
            $jobs[] = new SyncInstancePlugins($instance);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}
