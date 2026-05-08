<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Update\CoreUpdateRequestJob;
use App\Models\Instance;
use App\Models\Update;
use App\Models\UpdateRequest;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class CoreUpdateRequestJobTest extends TestCase
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

    public function test_core_update_request_job_creates_request_and_stores_moodle_job_id(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => true,
                'moodle_job_id' => 987,
            ], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $update = Update::factory()->for($instance)->create();
        $user = User::factory()->createQuietly();

        $job = new CoreUpdateRequestJob($instance, $update, $user, [
            'version' => $update->version,
        ]);

        $job->handle();

        $this->assertDatabaseHas('update_requests', [
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_CORE,
            'user_id' => $user->id,
            'status' => null,
            'moodle_job_id' => 987,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'model_id' => $update->id,
            'version' => $update->version,
            'release' => $update->release,
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_core_update_request_job_skips_when_pending_request_exists(): void
    {
        Http::fake();

        $instance = Instance::factory()->create();
        $update = Update::factory()->for($instance)->create();
        $user = User::factory()->createQuietly();
        UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_CORE,
        ]);

        $job = new CoreUpdateRequestJob($instance, $update, $user, [
            'version' => $update->version,
        ]);

        $job->handle();

        Http::assertNothingSent();
        $this->assertDatabaseCount('update_requests', 1);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_core_update_request_job_marks_request_failed_when_api_returns_failed_status(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => false,
                'message' => 'core failed',
            ], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $update = Update::factory()->for($instance)->create();
        $user = User::factory()->createQuietly();

        $job = new CoreUpdateRequestJob($instance, $update, $user, []);

        $job->handle();

        $this->assertDatabaseHas('update_requests', [
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_CORE,
            'status' => 0,
        ]);

        $this->assertDatabaseHas('update_request_items', [
            'model_id' => $update->id,
            'status' => 0,
            'error' => 'core failed',
        ]);

        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_core_update_request_job_releases_on_temporary_unavailability(): void
    {
        Http::fake([
            '*' => Http::response([], 503),
        ]);

        config()->set('queue.jobs.core-update.retry_after', 22);
        config()->set('queue.jobs.core-update.max_tries', 3);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $update = Update::factory()->for($instance)->create();
        $user = User::factory()->createQuietly();

        $job = new class($instance, $update, $user, []) extends CoreUpdateRequestJob
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

        $this->assertSame(22, $job->releasedDelay);
        $this->assertDatabaseHas('update_requests', [
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_CORE,
            'status' => null,
        ]);
    }
}
