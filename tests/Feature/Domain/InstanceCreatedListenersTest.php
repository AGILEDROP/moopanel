<?php

namespace Tests\Feature\Domain;

use App\Events\InstanceCreated;
use App\Jobs\ModuleApi\Sync;
use App\Models\Cluster;
use App\Models\BackupSetting;
use App\Models\BackupStorage;
use App\Models\Instance;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class InstanceCreatedListenersTest extends TestCase
{
    public function test_instance_created_event_creates_default_backup_records_and_sync_batch(): void
    {
        Bus::fake();

        $instance = Instance::factory()->create();
        $instance->setRelation('cluster', Cluster::factory()->make(['name' => 'Test Cluster']));

        event(new InstanceCreated($instance));

        $this->assertDatabaseHas('backup_settings', [
            'instance_id' => $instance->id,
            'auto_backups_enabled' => 0,
            'backup_interval' => 24,
            'backup_deletion_interval' => 7,
        ]);

        $this->assertDatabaseHas('backup_storages', [
            'instance_id' => $instance->id,
            'active' => 1,
            'name' => 'Local Storage',
            'storage_key' => 'local',
        ]);

        $this->assertEquals(1, BackupSetting::where('instance_id', $instance->id)->count());
        $this->assertEquals(1, BackupStorage::where('instance_id', $instance->id)->count());

        Bus::assertBatched(function ($batch) use ($instance) {
            if (count($batch->jobs) !== 3) {
                return false;
            }

            foreach ($batch->jobs as $job) {
                if (! $job instanceof Sync || $job->instance->isNot($instance)) {
                    return false;
                }
            }

            return collect($batch->jobs)->pluck('syncType')->sort()->values()->all() === ['core', 'info', 'plugins'];
        });
    }
}
