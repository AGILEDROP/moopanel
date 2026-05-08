<?php

namespace Tests\Unit\Services;

use App\Models\Instance;
use App\Services\ModuleApiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class ModuleApiServiceTest extends TestCase
{
    public function test_trigger_task_check_sends_expected_request(): void
    {
        Http::fake([
            '*' => Http::response(['status' => 2], 200),
        ]);

        $service = new ModuleApiService();
        $service->triggerTaskCheck('https://example.test', 'secret', ['id' => 12, 'type' => 'core_update']);

        Http::assertSent(fn ($request) => $request->method() === 'GET'
            && $request->url() === 'https://example.test/local/moopanel/server.php/tasks/check?id=12&type=core_update'
            && $request->hasHeader('X-API-KEY', 'secret'));
    }

    public function test_get_active_moodle_users_count_returns_online_count(): void
    {
        Http::fake([
            '*' => Http::response(['online' => 27], 200),
        ]);

        $count = (new ModuleApiService())->getActiveMoodleUsersCount(
            Instance::factory()->withApiKey('secret')->create(['url' => 'https://example.test']),
            10,
            20
        );

        $this->assertSame(27, $count);
    }

    public function test_get_active_moodle_users_count_logs_when_request_fails(): void
    {
        Log::spy();
        Http::fake([
            '*' => Http::response(['online' => null], 500),
        ]);

        (new ModuleApiService())->getActiveMoodleUsersCount(
            Instance::factory()->withApiKey('secret')->create(['url' => 'https://example.test']),
            10,
            20
        );

        Log::shouldHaveReceived('error')->once();
    }
}
