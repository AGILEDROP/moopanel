<?php

use App\Console\Commands\AzureApi\AzureAppDataSyncCommand;
use App\Console\Commands\Backup\AutomaticBackupCommand;
use App\Console\Commands\Backup\AutomaticBackupPruneCommand;
use App\Console\Commands\Backup\CheckPendingBackupResults;
use App\Console\Commands\Backup\DeleteSoftDeletedBackups;
use App\Console\Commands\ModuleApi\CheckPendingUpdateRequests;
use App\Console\Commands\ModuleApi\GetActiveMoodleUsersCount;
use App\Console\Commands\ModuleApi\RunZipPluginDeleteJob;
use App\Console\Commands\ModuleApi\SyncData;
use App\Console\Commands\SIS\SyncAccountsData;
use Illuminate\Support\Facades\Schedule;
use Spatie\Activitylog\CleanActivitylogCommand;

Schedule::command(CleanActivitylogCommand::class, ['--force'])->daily();

Schedule::command(AzureAppDataSyncCommand::class)->dailyAt('01:00');

Schedule::command(SyncAccountsData::class)->dailyAt('03:00');

Schedule::command(RunZipPluginDeleteJob::class)->hourly();
Schedule::command(SyncData::class)->everyTwoHours();
Schedule::command(GetActiveMoodleUsersCount::class)->hourly();

// Pending backup/update checkers
Schedule::command(CheckPendingUpdateRequests::class)->everyThreeMinutes();
Schedule::command(CheckPendingBackupResults::class)->everyThreeMinutes();

// Later can be set to one hour - 30mins because of testing
Schedule::command(AutomaticBackupCommand::class)->everyThirtyMinutes();
Schedule::command(AutomaticBackupPruneCommand::class)->daily();
Schedule::command(DeleteSoftDeletedBackups::class)->weekly();
