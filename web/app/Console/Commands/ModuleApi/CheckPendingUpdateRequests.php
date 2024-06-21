<?php

namespace App\Console\Commands\ModuleApi;

use App\Jobs\Update\CheckPendingUpdateRequestsJob;
use App\Models\UpdateRequest;
use Illuminate\Console\Command;
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
        $pendingUpdateRequests = UpdateRequest::where('status', UpdateRequest::STATUS_PENDING)->get();

        foreach ($pendingUpdateRequests as $updateRequest) {

            CheckPendingUpdateRequestsJob::dispatch($updateRequest);
        
        }
    }
}
