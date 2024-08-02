<?php

namespace App\Listeners;

use App\Events\InstanceCreated;
use App\Models\BackupStorage;
use Illuminate\Support\Facades\Log;

class CreateInstanceBackupStorage
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(InstanceCreated $event): void
    {
        $instance = $event->instance;

        // Create local backup storage with default values
        BackupStorage::create([
            'instance_id' => $instance->id,
            'active' => true,
            'name' => 'Local Storage',
            'storage_key' => 'local',
        ]);

        Log::info('Local backup storage created for instance '.$instance->name.' on '.$instance->cluster->name.' cluster.');
    }
}
