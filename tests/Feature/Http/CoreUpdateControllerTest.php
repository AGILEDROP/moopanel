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
}
