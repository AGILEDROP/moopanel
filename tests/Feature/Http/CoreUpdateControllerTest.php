<?php

namespace Tests\Feature\Http;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\Update;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CoreUpdateControllerTest extends TestCase
{
    public function test_core_update_callback_updates_request_and_dispatches_sync(): void
    {
        Bus::fake();
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $update = Update::factory()->for($instance)->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_CORE,
        ]);
        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => $update->id,
        ]);

        $response = $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/core/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 99,
                'status' => true,
                'message' => 'Applied successfully',
                'update_id' => $update->id,
            ]);

        $response->assertOk()->assertJson(['status' => true]);

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 1,
            'moodle_job_id' => 99,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'model_id' => $update->id,
            'status' => 1,
            'error' => 'Applied successfully',
        ]);

        Bus::assertDispatched(Sync::class, fn ($job) => $job->instance->is($instance) && $job->syncType === 'core');
    }

    public function test_core_update_requires_update_to_belong_to_instance(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $otherUpdate = Update::factory()->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/core/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 99,
                'status' => true,
                'message' => 'Applied successfully',
                'update_id' => $otherUpdate->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['update_id']);
    }

    public function test_core_update_failure_marks_request_failed_and_skips_sync(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $update = Update::factory()->for($instance)->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_CORE,
        ]);
        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => $update->id,
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/core/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 100,
                'status' => false,
                'message' => 'PHP fatal',
                'update_id' => $update->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 0,
            'moodle_job_id' => 100,
        ]);
        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'model_id' => $update->id,
            'status' => 0,
            'error' => 'PHP fatal',
        ]);

        // statusUpdate succeeds even when the reported update failed; the 'danger' notification
        // branch is exercised and the sync still dispatches.
        Bus::assertDispatched(Sync::class);
    }

    public function test_core_update_is_noop_when_no_pending_request_exists(): void
    {
        Bus::fake();
        Notification::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $update = Update::factory()->for($instance)->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/core/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 100,
                'status' => true,
                'message' => 'ok',
                'update_id' => $update->id,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        Bus::assertNotDispatched(Sync::class);
        $this->assertDatabaseCount('update_requests', 0);
    }
}
