<?php

namespace Tests\Unit\Models;

use App\Models\Instance;
use App\Models\UpdateRequest;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class UpdateRequestTest extends TestCase
{
    public function test_parse_status_maps_external_status_values(): void
    {
        $request = new UpdateRequest();

        $this->assertTrue($request->parseStatus(UpdateRequest::CHECK_STATUS_SUCCESS));
        $this->assertNull($request->parseStatus(UpdateRequest::CHECK_STATUS_PENDING));
        $this->assertFalse($request->parseStatus(UpdateRequest::CHECK_STATUS_FAIL));
        $this->assertFalse($request->parseStatus(UpdateRequest::CHECK_STATUS_NOT_FOUND));
    }

    public function test_status_name_accessor_returns_readable_value(): void
    {
        $this->assertSame('Pending', (new UpdateRequest(['status' => null]))->status_name);
        $this->assertSame('Success', (new UpdateRequest(['status' => true]))->status_name);
        $this->assertSame('Fail', (new UpdateRequest(['status' => false]))->status_name);
    }

    public function test_generate_name_uses_instance_name_request_type_and_current_time(): void
    {
        Carbon::setTestNow('2026-05-08 10:11:12');

        $generated = UpdateRequest::generateName('demo-instance', UpdateRequest::TYPE_CORE);

        $this->assertSame('demo-instance-core-update-08-05-26_10-11', $generated);
    }

    public function test_instance_can_check_for_pending_update_requests(): void
    {
        $instance = Instance::factory()->create();
        UpdateRequest::factory()->pending()->create([
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_PLUGIN,
            'user_id' => null,
        ]);
        UpdateRequest::factory()->successful()->create([
            'instance_id' => $instance->id,
            'type' => UpdateRequest::TYPE_CORE,
            'user_id' => null,
        ]);

        $this->assertTrue($instance->hasPendingUpdateRequest());
        $this->assertTrue($instance->hasPendingUpdateRequest(UpdateRequest::TYPE_PLUGIN));
        $this->assertFalse($instance->hasPendingUpdateRequest(UpdateRequest::TYPE_CORE));
    }
}
