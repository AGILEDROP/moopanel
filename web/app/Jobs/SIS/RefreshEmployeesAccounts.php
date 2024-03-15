<?php

namespace App\Jobs\SIS;

use App\Enums\AccountTypes;
use App\Models\UniversityMember;
use App\Services\SisApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RefreshEmployeesAccounts implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public UniversityMember $universityMember;

    public SisApiService $sisApiService;

    /**
     * Create a new job instance.
     */
    public function __construct(UniversityMember $universityMember)
    {
        $this->universityMember = $universityMember;
        $this->sisApiService = new SisApiService();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $sisEmployees = $this->sisApiService->getAllEmployees($this->universityMember->code, $this->universityMember->name);
        $this->sisApiService->updateAccountMembershipAndType($sisEmployees, $this->universityMember, AccountTypes::Employee->value);
    }
}
