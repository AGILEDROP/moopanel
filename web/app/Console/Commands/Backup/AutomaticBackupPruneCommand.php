<?php

namespace App\Console\Commands\Backup;

use App\Jobs\Backup\ScheduledBackupPruneJob;
use Illuminate\Console\Command;

class AutomaticBackupPruneCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto-prune';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Deletes obsolete backups according to instance settings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        ScheduledBackupPruneJob::dispatch();
    }
}
