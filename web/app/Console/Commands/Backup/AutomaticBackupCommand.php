<?php

namespace App\Console\Commands\Backup;

use App\Jobs\Backup\ScheduledBackupRequestJob;
use Illuminate\Console\Command;

class AutomaticBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:automatic-backup-command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs job that checks if automatic backups are enabled and creates a backup if needed.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ScheduledBackupRequestJob::dispatch();
    }
}
