<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\ModuleApi\Sync;
use App\Jobs\Update\CheckPendingUpdateRequestsJob;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\UpdateRequest;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

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
        $pendingUpdateRequests = UpdateRequest::where('status', UpdateRequest::STATUS_PENDING)
            ->get();

        Log::info('Triggered pending update check on update request ids: '.$pendingUpdateRequests->pluck('id')->implode(', ').' on instances: '.$pendingUpdateRequests->pluck('instance_id')->implode(', '));

        foreach ($pendingUpdateRequests as $updateRequest) {

            CheckPendingUpdateRequestsJob::dispatch($updateRequest);

        }

        $this->syncInstanceCore($pendingUpdateRequests);

        $this->syncInstancePlugins($pendingUpdateRequests);
    }

    private function syncInstanceCore(Collection $pendingUpdateRequests): void
    {
        $updatedCoreInstanceIds = $pendingUpdateRequests
            ->filter(fn (UpdateRequest $updateRequest) => $updateRequest->type === UpdateRequest::TYPE_CORE)
            ->map(fn (UpdateRequest $updateRequest) => $updateRequest->instance_id)
            ->toArray();

        $updatedCoreInstances = Instance::withoutGlobalScope(InstanceScope::class)
            ->whereIn('id', $updatedCoreInstanceIds)
            ->get();

        foreach ($updatedCoreInstances as $instance) {
            Sync::dispatch($instance, CoreSyncType::TYPE, 'Core sync on scheduled update check, failed!');
        }
    }

    private function syncInstancePlugins(Collection $pendingUpdateRequests): void
    {
        $updatedPluginInstanceIds = $pendingUpdateRequests
            ->filter(fn (UpdateRequest $updateRequest) => ($updateRequest->type === UpdateRequest::TYPE_PLUGIN || $updateRequest->type === UpdateRequest::TYPE_PLUGIN_ZIP))
            ->map(fn (UpdateRequest $updateRequest) => $updateRequest->instance_id)
            ->toArray();

        $updatedPluginInstances = Instance::withoutGlobalScope(InstanceScope::class)
            ->whereIn('id', $updatedPluginInstanceIds)
            ->get();

        foreach ($updatedPluginInstances as $instance) {
            Sync::dispatch($instance, PluginsSyncType::TYPE, 'Plugin sync on scheduled update check, failed!');
        }
    }
}
