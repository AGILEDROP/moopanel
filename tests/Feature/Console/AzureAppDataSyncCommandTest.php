<?php

namespace Tests\Feature\Console;

use App\Jobs\AzureApi\AzureAppDataSyncJob;
use App\Models\Instance;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class AzureAppDataSyncCommandTest extends TestCase
{
    public function test_command_dispatches_a_sync_job_for_each_instance(): void
    {
        Bus::fake();
        $instances = Instance::factory()->count(2)->create();

        $this->artisan('azure-api:app-data-sync')->assertExitCode(0);

        Bus::assertDispatched(AzureAppDataSyncJob::class, 2);
    }
}
