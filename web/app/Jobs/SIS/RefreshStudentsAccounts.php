<?php

namespace App\Jobs\SIS;

use App\Enums\AccountTypes;

class RefreshStudentsAccounts extends BaseSisJob
{
    public function handle(): void
    {
        $sisStudents = $this->sisApiService->getAllStudents($this->universityMember);
        $this->sisApiService->updateAccountMembershipAndType($sisStudents, $this->universityMember, AccountTypes::Student->value);
    }
}
