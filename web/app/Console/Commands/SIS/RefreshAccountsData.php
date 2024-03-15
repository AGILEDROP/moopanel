<?php

namespace App\Console\Commands\SIS;

use App\Jobs\SIS\RefreshEmployeesAccounts;
use App\Jobs\SIS\RefreshStudentsAccounts;
use App\Models\UniversityMember;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class RefreshAccountsData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sis:refresh-accounts-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Assign all existing accounts to university members and set the account type based on SIS endpoint data.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $jobs = [];
        $universityMembers = UniversityMember::all();
        foreach ($universityMembers as $universityMember) {
            $jobs[] = new RefreshEmployeesAccounts($universityMember);
            $jobs[] = new RefreshStudentsAccounts($universityMember);
        }

        Bus::batch($jobs)->dispatch();

        $this->info('Jobs dispatched.');
    }
}
