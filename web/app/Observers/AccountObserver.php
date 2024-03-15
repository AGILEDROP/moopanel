<?php

namespace App\Observers;

use App\Models\Account;

class AccountObserver
{
    public function deleted(Account $account): void
    {
        $account->universityMembers()->sync([]);
    }
}
