<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncCore extends Command
{
    protected $signature = 'module-api:sync-core';

    protected $description = 'Sync core updates for all existing instances.';

    public function handle(): int
    {
        $jobs = [];
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();
        foreach ($instances as $instance) {
            $jobs[] = new Sync($instance, CoreSyncType::TYPE, 'Core updates sync failed.');
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}
