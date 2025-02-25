<?php

namespace Database\Seeders;

use App\Models\BackupStorage;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Database\Seeder;

class DefaultBackupStorageSeeder extends Seeder
{
    public function run()
    {
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->get();

        foreach ($instances as $instance) {

            if ($instance->backupStorages->count() > 0) {
                continue;
            }

            BackupStorage::create([
                'instance_id' => $instance->id,
                'active' => true,
                'name' => 'Local Storage',
                'storage_key' => 'local',
                'url' => 'local://default',
                'key' => 'local_dummy_key',
                'secret' => 'local_dummy_secret',
                'bucket_name' => 'local_moodle_instance',
                'region' => 'local',
            ]);
        }
    }
}
