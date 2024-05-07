<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\SyncInstancePlugins;
use App\Models\Instance;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncPlugins extends Command
{
    public const SUCCESS = 0;

    public const FAILURE = 1;

    public const INVALID = 2;

    protected $signature = 'module-api-service:sync-plugins';

    protected $description = 'Sync plugins for existing instances.';

    public function handle(): int
    {
        $jobs = [];
        $instances = Instance::all();
        foreach ($instances as $instance) {
            $jobs[] = new SyncInstancePlugins($instance);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}
