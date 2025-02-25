<?php

namespace App\Listeners;

use App\Events\InstanceCreated;
use App\Jobs\ModuleApi\Sync;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SingleInstance\InfoSyncType;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use Illuminate\Support\Facades\Bus;

class SyncNewInstanceData
{
    public function handle(InstanceCreated $event): void
    {
        $jobs[] = new Sync($event->instance, InfoSyncType::TYPE, 'Instance information sync failed.');
        $jobs[] = new Sync($event->instance, CoreSyncType::TYPE, 'Core updates sync failed.');
        $jobs[] = new Sync($event->instance, PluginsSyncType::TYPE, 'Plugin sync failed!');

        Bus::batch($jobs)->dispatch();
    }
}
