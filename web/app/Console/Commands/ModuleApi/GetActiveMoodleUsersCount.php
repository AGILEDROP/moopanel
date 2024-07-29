<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\GetActiveMoodleUsersInLastHour;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;

class GetActiveMoodleUsersCount extends Command
{
    protected $signature = 'module-api:get-active-moodle-users-count';

    protected $description = 'Get number of active users in last hour for all existing instances.';

    public function handle(): int
    {
        $jobs = [];
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();
        foreach ($instances as $instance) {
            $jobs[] = new GetActiveMoodleUsersInLastHour($instance);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        Log::info('GetActiveMoodleUsersCount job dispatched.');

        return self::SUCCESS;
    }
}
