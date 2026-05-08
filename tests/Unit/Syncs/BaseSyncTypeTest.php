<?php

namespace Tests\Unit\Syncs;

use App\Enums\Status;
use App\Models\Instance;
use App\Models\Sync;
use App\UseCases\Syncs\SingleInstance\BaseSyncType;
use Exception;
use Filament\Actions\Action;
use Filament\Tables\Actions\Action as TableAction;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class BaseSyncTypeTest extends TestCase
{
    public function test_run_returns_false_when_base_class_sync_data_throws(): void
    {
        Log::spy();

        $instance = Instance::factory()->create(['status' => Status::Connected]);

        $result = (new BaseSyncType($instance))->run(silent: true);

        $this->assertFalse($result);
        Log::shouldHaveReceived('error')->once();
        $this->assertSame(Status::Disconnected, $instance->fresh()->status);
    }

    public function test_run_does_not_change_status_when_already_disconnected(): void
    {
        Log::spy();

        $instance = Instance::factory()->create(['status' => Status::Disconnected]);

        $this->assertFalse((new BaseSyncType($instance))->run(silent: true));
        $this->assertSame(Status::Disconnected, $instance->fresh()->status);
    }

    public function test_run_returns_true_when_subclass_sync_data_succeeds(): void
    {
        $instance = Instance::factory()->create();

        $sync = new class($instance) extends BaseSyncType
        {
            const TYPE = 'test-success';

            protected function syncData(): void
            {
                Sync::withoutGlobalScopes()->updateOrCreate([
                    'instance_id' => $this->instance->id,
                    'type' => static::TYPE,
                ], ['synced_at' => now()]);
            }
        };

        $this->assertTrue($sync->run(silent: true));
        $this->assertDatabaseHas('syncs', [
            'instance_id' => $instance->id,
            'type' => 'test-success',
        ]);
    }

    public function test_run_rolls_back_database_changes_on_failure(): void
    {
        Log::spy();

        $instance = Instance::factory()->create();

        $sync = new class($instance) extends BaseSyncType
        {
            const TYPE = 'test-rollback';

            protected function syncData(): void
            {
                Sync::withoutGlobalScopes()->create([
                    'instance_id' => $this->instance->id,
                    'type' => static::TYPE,
                ]);

                throw new Exception('boom');
            }
        };

        $this->assertFalse($sync->run(silent: true));
        $this->assertDatabaseMissing('syncs', [
            'instance_id' => $instance->id,
            'type' => 'test-rollback',
        ]);
    }

    public function test_get_latest_returns_most_recent_sync_for_type(): void
    {
        $instance = Instance::factory()->create();

        $sync = new class($instance) extends BaseSyncType
        {
            const TYPE = 'test-latest';
        };

        \Illuminate\Support\Carbon::setTestNow(now()->subDay());
        Sync::withoutGlobalScopes()->create([
            'instance_id' => $instance->id,
            'type' => 'test-latest',
        ]);

        \Illuminate\Support\Carbon::setTestNow(now()->addDay());
        $newer = Sync::withoutGlobalScopes()->create([
            'instance_id' => $instance->id,
            'type' => 'test-latest',
        ]);

        Sync::withoutGlobalScopes()->create([
            'instance_id' => $instance->id,
            'type' => 'other-type',
        ]);

        \Illuminate\Support\Carbon::setTestNow();

        $this->assertSame($newer->id, $sync->getLatest()->id);
    }

    public function test_get_latest_time_text_says_never_when_no_syncs_exist(): void
    {
        $instance = Instance::factory()->create();

        $sync = new class($instance) extends BaseSyncType
        {
            const TYPE = 'test-never';
        };

        $this->assertSame('Last sync: never', $sync->getLatestTimeText());
    }

    public function test_get_latest_time_text_uses_diff_for_humans(): void
    {
        $instance = Instance::factory()->create();

        $sync = new class($instance) extends BaseSyncType
        {
            const TYPE = 'test-time-text';
        };

        Sync::withoutGlobalScopes()->create([
            'instance_id' => $instance->id,
            'type' => 'test-time-text',
            'synced_at' => now()->subMinutes(5),
        ]);

        $this->assertStringContainsString('Last sync: ', $sync->getLatestTimeText());
        $this->assertStringNotContainsString('never', $sync->getLatestTimeText());
    }

    public function test_get_table_action_returns_action_with_expected_label_and_icon(): void
    {
        $instance = Instance::factory()->create();
        $sync = new BaseSyncType($instance);

        $action = $sync->getTableAction('sync-test', []);

        $this->assertInstanceOf(TableAction::class, $action);
        $this->assertSame('sync-test', $action->getName());
        $this->assertSame('Sync', $action->getLabel());
        $this->assertSame('heroicon-o-arrow-path', $action->getIcon());
    }

    public function test_get_header_action_returns_action_with_expected_label_and_icon(): void
    {
        $instance = Instance::factory()->create();
        $sync = new BaseSyncType($instance);

        $action = $sync->getHeaderAction('sync-header-test', []);

        $this->assertInstanceOf(Action::class, $action);
        $this->assertSame('sync-header-test', $action->getName());
        $this->assertSame('Sync', $action->getLabel());
        $this->assertSame('heroicon-o-arrow-path', $action->getIcon());
    }
}
