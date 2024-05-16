<?php

namespace App\Console\Commands\SIS;

use App\Jobs\SIS\SyncEmployeesAccounts;
use App\Jobs\SIS\SyncStudentsAccounts;
use App\Models\UniversityMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncAccountsData extends Command
{
    protected $signature = 'sis:sync-accounts-data';

    protected $description = 'Assign all existing accounts to university members and set the account type based on SIS endpoint data.';

    public function handle(): int
    {
        $jobs = [];
        $universityMembers = UniversityMember::all();
        foreach ($universityMembers as $universityMember) {
            $jobs[] = new SyncEmployeesAccounts($universityMember);
            $jobs[] = new SyncStudentsAccounts($universityMember);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');

        return self::SUCCESS;
    }
}
