<?php

namespace Tests\Feature\Http;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class PluginZipUpdateControllerTest extends TestCase
{
    public function test_plugin_zip_update_maps_success_by_zip_path(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
        ]);

        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => null,
            'zip_path' => '/tmp/a.zip',
        ]);
        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => null,
            'zip_path' => '/tmp/b.zip',
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/zip-plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 55,
                'updates' => [
                    ['link' => '/tmp/a.zip', 'status' => true, 'component' => 'mod_forum', 'version' => '2.0.0', 'error' => null],
                    ['link' => '/tmp/b.zip', 'status' => false, 'component' => 'mod_quiz', 'version' => '3.0.0', 'error' => 'bad zip'],
                ],
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 0,
            'moodle_job_id' => 55,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'zip_path' => '/tmp/a.zip',
            'status' => 1,
            'component' => 'mod_forum',
            'version' => '2.0.0',
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'zip_path' => '/tmp/b.zip',
            'status' => 0,
            'error' => 'bad zip',
        ]);

        Bus::assertDispatched(Sync::class, fn ($job) => $job->syncType === 'plugins');
    }

    public function test_plugin_zip_update_uses_fallbacks_for_missing_component_and_version(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
        ]);

        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => null,
            'zip_path' => '/tmp/c.zip',
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/zip-plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 56,
                'updates' => [
                    ['link' => '/tmp/c.zip', 'status' => true, 'component' => 'mod_forum', 'error' => null],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'zip_path' => '/tmp/c.zip',
            'component' => 'mod_forum',
            'version' => ' - ',
        ]);
    }

    public function test_plugin_zip_update_is_idempotent_without_pending_request(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        UpdateRequest::factory()->successful()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
            'moodle_job_id' => 12,
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/zip-plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 88,
                'updates' => [
                    ['link' => '/tmp/noop.zip', 'status' => true, 'component' => 'mod_forum', 'error' => null],
                ],
            ])
            ->assertOk();

        Bus::assertNotDispatched(Sync::class);
    }

    public function test_plugin_zip_update_validates_required_fields(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/zip-plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 56,
                'updates' => [
                    ['status' => 'oops'],
                ],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['updates.0.link', 'updates.0.status', 'updates.0.component']);
    }

    public function test_plugin_zip_update_marks_request_failed_when_all_items_fail(): void
    {
        Bus::fake();

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
        ]);

        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => null,
            'zip_path' => '/tmp/x.zip',
        ]);
        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => null,
            'zip_path' => '/tmp/y.zip',
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/zip-plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 99,
                'updates' => [
                    ['link' => '/tmp/x.zip', 'status' => false, 'component' => 'mod_forum', 'version' => '1.0.0', 'error' => 'broken'],
                    ['link' => '/tmp/y.zip', 'status' => false, 'component' => 'mod_quiz', 'version' => '2.0.0', 'error' => 'broken'],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 0,
        ]);

        Bus::assertDispatched(Sync::class);
    }
}
