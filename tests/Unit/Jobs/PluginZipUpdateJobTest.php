<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Update\PluginZipUpdateJob;
use App\Models\Instance;
use App\Models\UpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class PluginZipUpdateJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('instances', 'short_name')) {
            Schema::table('instances', function (Blueprint $table) {
                $table->string('short_name')->nullable();
            });
        }
    }

    public function test_plugin_zip_update_job_creates_request_items_from_temp_payload_and_saves_moodle_job_id(): void
    {
        Http::fake([
            '*' => Http::response([
                'moodle_job_id' => 321,
            ], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $user = User::factory()->createQuietly();
        $payload = [
            'updates' => [
                ['link' => '/tmp/forum.zip'],
            ],
            'temp_updates_data' => [
                ['zip_name' => 'forum.zip', 'zip_path' => '/tmp/forum.zip'],
                ['zip_name' => 'quiz.zip', 'zip_path' => '/tmp/quiz.zip'],
            ],
        ];

        $job = new PluginZipUpdateJob($instance, $user, $payload);
        $job->handle();

        $this->assertDatabaseHas('update_requests', [
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
            'moodle_job_id' => 321,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'zip_name' => 'forum.zip',
            'zip_path' => '/tmp/forum.zip',
        ]);
        $this->assertDatabaseHas('update_request_items', [
            'zip_name' => 'quiz.zip',
            'zip_path' => '/tmp/quiz.zip',
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_plugin_zip_update_job_skips_when_pending_request_exists(): void
    {
        Http::fake();

        $instance = Instance::factory()->create();
        $user = User::factory()->createQuietly();
        UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
        ]);

        $job = new PluginZipUpdateJob($instance, $user, [
            'updates' => [],
            'temp_updates_data' => [],
        ]);
        $job->handle();

        Http::assertNothingSent();
        $this->assertDatabaseCount('update_requests', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_plugin_zip_update_job_releases_on_temporary_unavailability(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        config()->set('queue.jobs.plugin-zip-update.retry_after', 17);
        config()->set('queue.jobs.plugin-zip-update.max_tries', 3);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $user = User::factory()->createQuietly();

        $job = new class($instance, $user, ['updates' => [], 'temp_updates_data' => []]) extends PluginZipUpdateJob
        {
            public ?int $releasedDelay = null;

            public function attempts(): int
            {
                return 1;
            }

            public function release($delay = 0): void
            {
                $this->releasedDelay = $delay;
            }
        };

        $job->handle();

        $this->assertSame(17, $job->releasedDelay);
    }
}
