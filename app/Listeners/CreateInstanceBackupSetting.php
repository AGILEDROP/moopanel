<?php

namespace App\Listeners;

use App\Events\InstanceCreated;
use App\Models\BackupSetting;
use Illuminate\Support\Facades\Log;

class CreateInstanceBackupSetting
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

        // Create backup settings with default values
        BackupSetting::create([
            'instance_id' => $instance->id,
            'auto_backups_enabled' => false,
            'backup_interval' => 24,
            'backup_deletion_interval' => 7,
        ]);

        Log::info('Backup settings created for instance '.$instance->name.' on '.$instance->cluster->name.' cluster.');
    }
}
