<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('activitylog:clean --force')->daily();

        $schedule->command('sis:sync-accounts-data')->dailyAt('03:00');

        $schedule->command('module-api:zip-plugin-file-delete')->hourly();
        $schedule->command('module-api:sync-data')->everyTwoHours();
        $schedule->command('module-api:get-active-moodle-users-count')->hourly();

        // Penging backup/update checkers
        $schedule->command('module-api:check-pending-update-requests')->everyThreeMinutes();
        $schedule->command('backup:check-pending-backup-results')->everyThreeMinutes();

        $schedule->command('backup:automatic-backup-command')->hourly();
        $schedule->command('backup:auto-prune')->daily();
        $schedule->command('backup:clean')->weekly();
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        $this->load(__DIR__.'/Commands/Backup');
        $this->load(__DIR__.'/Commands/ModuleApi');
        $this->load(__DIR__.'/Commands/SIS');

        require base_path('routes/console.php');
    }
}
