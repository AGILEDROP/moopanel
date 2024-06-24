<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\Sync;
use App\Jobs\Update\CheckPendingUpdateRequestsJob;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\UpdateRequest;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use Illuminate\Console\Command;

class CheckPendingUpdateRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'module-api:check-pending-update-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for pending update requests and resends them if necessary.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Check pending update requests
        $pendingUpdateRequests = UpdateRequest::where('status', UpdateRequest::STATUS_PENDING)->get();

        foreach ($pendingUpdateRequests as $updateRequest) {

            CheckPendingUpdateRequestsJob::dispatch($updateRequest);
        }

        // Sync instances
        $updatedInstanceIds = $pendingUpdateRequests->map(function ($updateRequest) {
            return $updateRequest->instance_id;
        })->toArray();

        $updatedInstances = Instance::withoutGlobalScope(InstanceScope::class)
            ->whereIn('id', $updatedInstanceIds)
            ->get();

        foreach ($updatedInstances as $instance) {
            Sync::dispatch($instance, PluginsSyncType::TYPE, 'Plugin sync on scheduled update check, failed!');
        }
    }
}
