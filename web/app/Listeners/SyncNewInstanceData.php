<?php

namespace App\Listeners;

use App\Events\InstanceCreated;
use App\Jobs\ModuleApi\SyncInstanceCoreUpdates;
use App\Jobs\ModuleApi\SyncInstanceInfo;
use App\Jobs\ModuleApi\SyncInstancePlugins;
use Illuminate\Support\Facades\Bus;

class SyncNewInstanceData
{
    public function handle(InstanceCreated $event): void
    {
        $jobs[] = new SyncInstanceInfo($event->instance);
        $jobs[] = new SyncInstanceCoreUpdates($event->instance);
        $jobs[] = new SyncInstancePlugins($event->instance);

        Bus::batch($jobs)->dispatch();
    }
}
