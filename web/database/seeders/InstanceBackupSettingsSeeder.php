<?php

namespace Database\Seeders;

use App\Models\BackupSetting;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class InstanceBackupSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();

        foreach ($instances as $instance) {
            // Check if the instance already has backup settings
            $hasSettings = BackupSetting::where('instance_id', $instance->id)->exists();

            if (! $hasSettings) {
                // Create backup settings with default values
                BackupSetting::create([
                    'instance_id' => $instance->id,
                    'auto_backups_enabled' => false,
                    'backup_interval' => 24,
                    'backup_deletion_interval' => 7,
                ]);

                Log::info('Backup settings created for instance '.$instance->name);
            }
        }
    }
}
