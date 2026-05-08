<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Update\PluginUpdateJob;
use App\Models\Instance;
use App\Models\Update;
use App\Models\UpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class PluginUpdateJobTest extends TestCase
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

    public function test_plugin_update_job_creates_request_items_and_saves_moodle_job_id(): void
    {
        Http::fake([
            '*' => Http::response([
                'moodle_job_id' => 654,
            ], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $user = User::factory()->createQuietly();
        $firstUpdate = Update::factory()->for($instance)->create([
            'version' => '1.0.0',
            'release' => 'r1',
            'download' => 'https://files/forum.zip',
        ]);
        $secondUpdate = Update::factory()->for($instance)->create([
            'version' => '2.0.0',
            'release' => 'r2',
            'download' => 'https://files/quiz.zip',
        ]);
        $payload = [
            'updates' => [
                [
                    'model_id' => $firstUpdate->id,
                    'component' => 'mod_forum',
                    'version' => '1.0.0',
                    'release' => 'r1',
                    'download' => 'https://files/forum.zip',
                ],
                [
                    'model_id' => $secondUpdate->id,
                    'component' => 'mod_quiz',
                    'version' => '2.0.0',
                    'release' => 'r2',
                    'download' => 'https://files/quiz.zip',
                ],
            ],
        ];

        $job = new PluginUpdateJob($instance, $user, $payload);
        $job->handle();

        $this->assertDatabaseHas('update_requests', [
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_PLUGIN,
            'moodle_job_id' => 654,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'component' => 'mod_forum',
            'version' => '1.0.0',
        ]);
        $this->assertDatabaseHas('update_request_items', [
            'component' => 'mod_quiz',
            'version' => '2.0.0',
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_plugin_update_job_skips_when_pending_request_exists(): void
    {
        Http::fake();

        $instance = Instance::factory()->create();
        $user = User::factory()->createQuietly();
        UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_PLUGIN,
        ]);

        $job = new PluginUpdateJob($instance, $user, ['updates' => []]);
        $job->handle();

        Http::assertNothingSent();
        $this->assertDatabaseCount('update_requests', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_plugin_update_job_releases_on_temporary_unavailability(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        config()->set('queue.jobs.plugin-update.retry_after', 19);
        config()->set('queue.jobs.plugin-update.max_tries', 3);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $user = User::factory()->createQuietly();

        $job = new class($instance, $user, ['updates' => []]) extends PluginUpdateJob
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

        $this->assertSame(19, $job->releasedDelay);
    }
}
