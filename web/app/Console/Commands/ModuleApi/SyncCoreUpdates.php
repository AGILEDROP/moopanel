<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\SyncInstanceCoreUpdates;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncCoreUpdates extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    public const INVALID = 2;

    protected $signature = 'module-api-service:sync-core-updates';

    protected $description = 'Sync core updates for all existing instances.';

    public function handle(): int
    {
        $jobs = [];
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();
        foreach ($instances as $instance) {
            $jobs[] = new SyncInstanceCoreUpdates($instance);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}