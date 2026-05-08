<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Update\CheckPendingUpdateRequestsJob;
use App\Models\Instance;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckPendingUpdateRequestsJobTest extends TestCase
{
    public function test_job_updates_request_and_items_when_remote_status_succeeds(): void
    {
        Http::fake([
            '*' => Http::response(['status' => UpdateRequest::CHECK_STATUS_SUCCESS], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret')->create(['url' => 'https://example.test']);
        $user = User::factory()->createQuietly();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'type' => UpdateRequest::TYPE_CORE,
            'moodle_job_id' => 321,
        ]);
        UpdateRequestItem::factory()->create([
            'update_request_id' => $updateRequest->id,
            'model_id' => null,
        ]);

        (new CheckPendingUpdateRequestsJob($updateRequest))->handle();

        $this->assertDatabaseHas('update_requests', [
            'id' => $updateRequest->id,
            'status' => 1,
        ]);
        $this->assertDatabaseHas('update_request_items', [
            'update_request_id' => $updateRequest->id,
            'status' => 1,
        ]);
    }

    public function test_job_throws_when_moodle_job_id_is_missing(): void
    {
        $this->expectException(\Exception::class);

        $instance = Instance::factory()->create();
        $user = User::factory()->createQuietly();
        $updateRequest = UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'user_id' => $user->id,
            'moodle_job_id' => null,
        ]);

        (new CheckPendingUpdateRequestsJob($updateRequest))->handle();
    }
}
