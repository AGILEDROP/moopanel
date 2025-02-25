<?php

namespace App\Jobs\AzureApi;

use App\Models\Instance;
use App\Services\AzureApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AzureAppDataSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Instance $instance)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        if (is_null($this->instance->azure_app_id)) {
            Log::warning("Instance {$this->instance->name} with id {$this->instance->id} does not have app_id set. Skipping app-data sync.");

            return;
        }

        $azureApi = new AzureApiService($this->instance);

        $azureAppInfo = $azureApi->applicationInfo($this->instance->azure_app_id);

        if (is_null($azureAppInfo)) {
            Log::error("Failed to fetch app_data for instance {$this->instance->name} with id {$this->instance->id} via Azure API job.");

            return;
        }

        Log::info("Fetched app_data for instance {$this->instance->name} with id {$this->instance->id} via Azure API job with data: ".json_encode($azureAppInfo));

        $this->instance->update([
            'app_info' => json_encode($azureAppInfo),
        ]);

        Log::info("Successfully synced app_data for instance {$this->instance->name} with id {$this->instance->id} via Azure API job.");
    }
}
