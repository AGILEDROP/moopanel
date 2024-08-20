<?php

namespace App\Console\Commands\AzureApi;

use App\Jobs\AzureApi\AzureAppDataSyncJob;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Console\Command;

class AzureAppDataSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'azure-api:app-data-sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Syncy instances app data with appropriate Azure app';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        Instance::withoutGlobalScope(InstanceScope::class)->get()->each(function ($instance) {
            AzureAppDataSyncJob::dispatch($instance);
        });
    }
}
