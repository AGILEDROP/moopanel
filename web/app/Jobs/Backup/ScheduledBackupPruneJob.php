<?php

namespace App\Jobs\Backup;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class ScheduledBackupPruneJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // za vsako instanco ki ima omogočeno backup brisanje,
        // najdi vse backup_results, ki še niso izbrisani, so automatic, so true in so starejši od nastavitve(v dneh)
        // day backup_result->url v array in pošlji na moodle
        // če je response 200, soft-delete backup_results

        $instanceBackupGroups = Instance::withoutGlobalScope(InstanceScope::class)
            ->select('backup_results.*')
            ->join('backup_settings', 'instances.id', '=', 'backup_settings.instance_id')
            ->join('backup_results', 'instances.id', '=', 'backup_results.instance_id')
            ->where('backup_settings.auto_deletion_enabled', true)
            ->where('backup_results.status', true)
            ->whereNull('backup_results.deleted_at')
            ->whereNull('backup_results.user_id')
            ->where(function ($query) {
                $query->whereNull('backup_settings.backup_deletion_interval')
                    ->orWhereRaw('backup_results.updated_at < NOW() - (backup_settings.backup_deletion_interval || \' days\')::interval');
            })
            ->get()
            ->groupBy('instance_id')
            ->map(function (Collection $instanceBackupResults) {
                return $instanceBackupResults->select('id', 'url', 'course_id', 'updated_at')->toArray();
            });

        foreach ($instanceBackupGroups as $instanceId => $backupResults) {

            // Keep at least latest course auto-backups - delete the rest
            // Pop out latest auto-backup for each course
            $backupResultsToDelete = collect($backupResults)
                ->groupBy('course_id')
                // delete backups only on courses that have more than one backup
                ->filter(fn (Collection $courseBackupResults) => $courseBackupResults->count() > 1)
                ->map(function (Collection $courseBackupResults) {
                    $sorted = $courseBackupResults->sortBy('updated_at');
                    $sorted->pop();

                    return $sorted;
                })
                ->flatten(1)
                ->select('id', 'url')
                ->toArray();

            $payload = [
                'instance_id' => $instanceId,
                // TODO: add instances current storage in V2.0
                'storage' => 'local',
                'mode' => 'auto',
                'credentials' => [],
                'backups' => array_map(function ($backupResult) {
                    return [
                        'backup_result_id' => $backupResult['id'],
                        'link' => (isset($backupResult['url']) && ! is_null($backupResult['url'])) ? $backupResult['url'] : '',
                    ];
                }, $backupResultsToDelete),
            ];

            Log::info(__('Scheduling to delete :count/:all backups for instance :instanceId. Each course was left with its most recent auto-backup.', [
                'count' => count($payload['backups']),
                'all' => count($backupResults),
                'instanceId' => $instanceId,
            ]));

            DeleteOldBackupsJob::dispatch($instanceId, $payload);
        }

        Log::info('Done with scheduling backup auto-deletion jobs for instances.');
    }
}
