<?php

namespace App\Jobs\SIS;

use App\Enums\AccountTypes;

class SyncEmployeesAccounts extends BaseSisJob
{
    public function handle(): void
    {
        $sisEmployees = $this->sisApiService->getAllEmployees($this->universityMember);
        $this->sisApiService->updateAccountMembershipAndType($sisEmployees, $this->universityMember, AccountTypes::Employee->value);
    }
}
