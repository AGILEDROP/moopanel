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
                return $instanceBackupResults->select('id', 'url', 'updated_at')->toArray();
            })
            ->toArray();

        Log::info('Scheduled backup prune job. Found '.count($instanceBackupGroups).' instances with backups to prune.');

        foreach ($instanceBackupGroups as $instanceId => $backupResults) {

            // Do not perform backup deletion if there is only one auto-backup left for the instance
            if (count($backupResults) <= 1) {
                Log::info(__('Skipping backup prune for instance :instanceId, only one auto backup left.', [
                    'instanceId' => $instanceId,
                ]));

                continue;
            }

            // sort backups by updated_at ascending
            usort($backupResults, function ($a, $b) {
                return $a['updated_at'] <=> $b['updated_at'];
            });

            // do not delete latest auto-backup
            // Note: We wont delete only the latest of auto-backup that are older than days in settings
            // there might also be even more recent auto backups that are not yet old enough to be deleted
            // for that, we will need another query, so we are avoiding this in current version
            array_pop($backupResults);

            $payload = [
                'instance_id' => $instanceId,
                // TODO: add instances current storage in V2.0
                'storage' => 'local',
                'mode' => 'auto',
                'credentials' => [],
                'backups' => array_reduce($backupResults, function ($carry, $backupResult) {
                    $carry[] = [
                        'backup_result_id' => $backupResult['id'],
                        'link' => (isset($backupResult['url']) && ! is_null($backupResult['url'])) ? $backupResult['url'] : '',
                    ];

                    return $carry;
                }, []),
            ];

            Log::info(__('Scheduling to delete :count backups for instance :instanceId.', [
                'count' => count($payload['backups']),
                'instanceId' => $instanceId,
            ]));

            // Run job to submit backup deletion request per each instance
            DeleteOldBackupsJob::dispatch($instanceId, $payload);
        }

        Log::info('Done with scheduling backup prune jobs for instances.');
    }
}
