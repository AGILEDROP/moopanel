<?php

namespace Tests\Feature\Http;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\Update;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PluginUpdateControllerTest extends TestCase
{
    public function test_plugin_update_marks_request_success_when_all_items_succeed(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN,
        ]);

        $first = Update::factory()->for($instance)->create();
        $second = Update::factory()->for($instance)->create();
        UpdateRequestItem::factory()->create(['update_request_id' => $updateRequest->id, 'model_id' => $first->id]);
        UpdateRequestItem::factory()->create(['update_request_id' => $updateRequest->id, 'model_id' => $second->id]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 41,
                'updates' => [
                    ['model_id' => $first->id, 'status' => true, 'component' => 'mod_forum', 'error' => null],
                    ['model_id' => $second->id, 'status' => true, 'component' => 'mod_quiz', 'error' => null],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 1,
            'moodle_job_id' => 41,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'model_id' => $first->id,
            'status' => 1,
        ]);

        Bus::assertDispatched(Sync::class, fn ($job) => $job->instance->is($instance) && $job->syncType === 'plugins');
    }

    public function test_plugin_update_marks_request_failed_when_any_item_fails(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN,
        ]);

        $success = Update::factory()->for($instance)->create();
        $failure = Update::factory()->for($instance)->create();
        UpdateRequestItem::factory()->create(['update_request_id' => $updateRequest->id, 'model_id' => $success->id]);
        UpdateRequestItem::factory()->create(['update_request_id' => $updateRequest->id, 'model_id' => $failure->id]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 42,
                'updates' => [
                    ['model_id' => $success->id, 'status' => true, 'component' => 'mod_forum', 'error' => null],
                    ['model_id' => $failure->id, 'status' => false, 'component' => 'mod_quiz', 'error' => 'boom'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 0,
            'moodle_job_id' => 42,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'model_id' => $failure->id,
            'status' => 0,
            'error' => 'boom',
        ]);

        Bus::assertDispatched(Sync::class);
    }

    public function test_plugin_update_is_idempotent_when_no_pending_request_exists(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $existing = UpdateRequest::factory()->successful()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN,
            'moodle_job_id' => 77,
        ]);
        $update = Update::factory()->for($instance)->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 88,
                'updates' => [
                    ['model_id' => $update->id, 'status' => true, 'component' => 'mod_forum', 'error' => null],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('update_requests', [
            'id' => $existing->id,
            'moodle_job_id' => 77,
            'status' => 1,
        ]);

        Bus::assertNotDispatched(Sync::class);
    }

    public function test_plugin_update_validates_required_nested_fields(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 99,
                'updates' => [
                    ['model_id' => 999999, 'component' => 'mod_forum'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['updates.0.model_id', 'updates.0.status']);
    }
}
