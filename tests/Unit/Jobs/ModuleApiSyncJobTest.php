<?php

namespace Tests\Unit\Jobs;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Services\ModuleApiService;
use Mockery;
use Tests\TestCase;

class ModuleApiSyncJobTest extends TestCase
{
    public function test_job_completes_when_service_sync_succeeds(): void
    {
        $job = new class(Instance::factory()->create(), 'info') extends Sync
        {
            public bool $failedFlag = false;

            public function fail($exception = null): void
            {
                $this->failedFlag = true;
            }
        };
        $job->moduleApiService = Mockery::mock(ModuleApiService::class);
        $job->moduleApiService->shouldReceive('sync')->once()->andReturn(true);

        $job->handle();

        $this->assertFalse($job->failedFlag);
    }

    public function test_job_marks_itself_failed_when_service_sync_fails(): void
    {
        $job = new class(Instance::factory()->create(), 'info', 'Expected failure') extends Sync
        {
            public bool $failedFlag = false;

            public function fail($exception = null): void
            {
                $this->failedFlag = true;
            }
        };
        $job->moduleApiService = Mockery::mock(ModuleApiService::class);
        $job->moduleApiService->shouldReceive('sync')->once()->andReturn(false);

        $job->handle();

        $this->assertTrue($job->failedFlag);
    }
}
