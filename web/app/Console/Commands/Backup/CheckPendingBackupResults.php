<?php

namespace App\Console\Commands\Backup;

use App\Jobs\Backup\CheckPendingBackupResultsJob;
use App\Models\BackupResult;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckPendingBackupResults extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:check-pending-backup-results';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for pending backups that are in process of creation and backup results that are in process of being deleted.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->checkBackupCreations();

        $this->checkBackupDeletions();

        // TODO: dispatch jobs for checking the backups in restore process

        $this->info('Checked pending backup results to create and delete.');
    }

    /**
     * Check pending backup results to create backups
     */
    private function checkBackupCreations(): void
    {
        $pendingBackupResultsToCreate = BackupResult::whereNull('status')
            ->whereNotNull('moodle_job_id')
            ->get();

        foreach ($pendingBackupResultsToCreate as $backupResult) {
            Log::info(__(
                'Running create backup check on backup result with id :backup_result_id and moodle job id :moodle_job_id.',
                [
                    'backup_result_id' => $backupResult->id,
                    'moodle_job_id' => $backupResult->moodle_job_id,
                ]
            ));

            CheckPendingBackupResultsJob::dispatch($backupResult, BackupResult::JOB_KEY_CREATE);
        }
    }

    /**
     * Check pending backup results to delete
     */
    private function checkBackupDeletions(): void
    {
        $pendingBackupResultsToDelete = BackupResult::where('status', true)
            ->where('in_deletion_process', true)
            ->whereNotNull('moodle_job_id')
            ->get();

        foreach ($pendingBackupResultsToDelete as $backupResult) {
            Log::info(__(
                'Running delete backup check on backup result with id :backup_result_id and moodle job id :moodle_job_id.',
                [
                    'backup_result_id' => $backupResult->id,
                    'moodle_job_id' => $backupResult->moodle_job_id,
                ]
            ));

            CheckPendingBackupResultsJob::dispatch($backupResult, BackupResult::JOB_KEY_DELETE);
        }
    }
}
