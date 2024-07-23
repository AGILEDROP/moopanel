<?php

namespace App\Console\Commands\Backup;

use App\Models\BackupResult;
use Illuminate\Console\Command;

class DeleteSoftDeletedBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes soft-deleted backup results on a period(probably once a week)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        BackupResult::withTrashed()->whereNotNull('deleted_at')->forceDelete();
        $this->info('Soft-deleted backup results have been deleted.');
    }
}
