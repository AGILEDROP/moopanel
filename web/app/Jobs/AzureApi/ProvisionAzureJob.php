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

class ProvisionAzureJob implements ShouldQueue
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
        $appRoleAssignmentId = $azureApiService->assignUserToUniversityMemberApp($instance, $this->account);

        // save app_role_assignment_id to the pivot table
        if (is_string($appRoleAssignmentId)) {

            if ($this->universityMember->accounts->contains($this->account->id)) {
                $this->universityMember->accounts()->updateExistingPivot($this->account->id, ['app_role_assignment_id' => $appRoleAssignmentId]);
            } else {
                $this->universityMember->accounts()->attach($this->account->id, ['app_role_assignment_id' => $appRoleAssignmentId]);
            }

            Log::info("User of type {$this->type} assigned to university member app for account {$this->account->id} and university member: {$this->universityMember->code}, {$this->universityMember->name},  with app_role_assignment_id: {$appRoleAssignmentId}.");

            return;
        }

        if ($appRoleAssignmentId === false) {
            Log::error("Error assigning user of type {$this->type} to university member app for account {$this->account->id} and university member {$this->universityMember->code}. Missing app_role_assignment_id returned from Azure AD.");
        }

        Log::info("User of type {$this->type} already assigned to university member app for account {$this->account->id} and university member: {$this->universityMember->code}, {$this->universityMember->name}.");
    }
}
