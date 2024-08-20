<?php

namespace App\Jobs\AzureApi;

use App\Models\Account;
use App\Models\Scopes\InstanceScope;
use App\Models\UniversityMember;
use App\Services\AzureApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DeprovisionAzureJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private UniversityMember $universityMember,
        private Account $account,
        private string $type
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instance = $this->universityMember->instances()->withoutGlobalScope(InstanceScope::class)->get()->first();

        if (! $instance) {
            Log::error("Error provisioning users to azure app - missing instance for university member {$this->universityMember->code}.");

            return;
        }

        $azureApiService = new AzureApiService($instance);

        // perform the provisioning - request to Azure API
        $unassignedSuccessfully = $azureApiService->unassignUserfromUniversityMemberApp($this->universityMember, $this->account);

        if (! $unassignedSuccessfully) {
            Log::error("Error unassigning user from university member app for account {$this->account->id} and university member {$this->universityMember->code}.");

            return;
        }

        // Detach user from university member
        $this->universityMember->accounts()->detach($this->account->id);

        Log::info("User of type {$this->type} unassigned from university member app for account {$this->account->id} and university member: {$this->universityMember->code}, {$this->universityMember->name}.");
    }
}
