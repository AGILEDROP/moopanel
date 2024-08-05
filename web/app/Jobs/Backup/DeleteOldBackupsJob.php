<?php

namespace App\Jobs\Backup;

use App\Models\BackupResult;
use App\Models\BackupSetting;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use App\Services\ModuleApiService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class DeleteOldBackupsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Instance $instance;

    // Has to be max_tries(from config) + 1
    public $tries = 4;

    public string $deletionType;

    /**
     * Create a new job instance.
     */
    public function __construct(private int $instanceId, private array $payload, private bool $isManual = false, private ?User $userToNotify = null)
    {
        $this->instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->instanceId);
        $this->deletionType = $isManual ? 'manual-deletion' : 'auto-deletion';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // Remove pending deletion requests from the payload
            $this->removePendingDeletionRequests();

            // Skip deletion request if no backups are specified
            // Notify user
            if (! $this->payload['backups'] || empty($this->payload['backups'])) {

                if ($this->isManual) {
                    Notification::make()
                        ->warning()
                        ->title(__('Skipping backup deletion'))
                        ->body(__('Selected backups are already in deletion process. Please wait for the process to complete or check system logs for more information.'))
                        ->icon('heroicon-o-circle-stack')
                        ->iconColor('warning')
                        ->sendToDatabase($this->userToNotify);
                }

                Log::info('No course-backups for instanceID: '.$this->payload['instance_id'].' specified. Aborting backup deletion request with payload: '.json_encode($this->payload));

                return;
            }

            $moduleApiService = new ModuleApiService();

            $response = $moduleApiService->triggerCourseBackupDeletion($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (! $response->ok()) {

                // Retry job if service is temporarily unavailable
                $maxRetries = config('queue.jobs.course-backup-deletion.max_tries') ?? 3;
                if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                    Log::info('Retrying course backup deletion request for instance: '.$this->instance->name.' as the service is temporarily unavailable.');

                    $retryAfter = config('queue.jobs.course-backup-deletion.retry_after');
                    $this->release($retryAfter);

                    return;
                }

                Log::error('Course backup deletion request for instance failed with status code and body: '.$response->status().' - '.$response->body());

                throw new \Exception('Course backup deletion request failed with status code: '.$response->status().'.');
            }

            $response = $response->json();

            Log::info('Course backup deletion response: '.json_encode($response));

            if (isset($response['backups']) && $response['backups']) {

                foreach ($response['backups'] as $backup) {
                    if ($backup['status'] === true) {
                        BackupResult::where('id', $backup['backup_result_id'])
                            ->update(
                                [
                                    'in_deletion_process' => true,
                                    'moodle_job_id' => $backup['moodle_job_id'],
                                ]
                            );
                    }
                }

                $inDeletionProcessCount = count(array_filter($response['backups'], function ($backup) {
                    return $backup['status'] === true;
                }));

                $allCount = count($response['backups']);

                $notificationTitle = $this->getNotificationTitle($inDeletionProcessCount, $allCount);

                if ($this->isManual) {
                    $notificationColor = $this->getNotificationColor($inDeletionProcessCount, $allCount);
                    $notificationBody = $this->getLogBody($inDeletionProcessCount, $allCount, $this->payload, true, 3);

                    Notification::make()
                        ->status($notificationColor)
                        ->title($notificationTitle)
                        ->body($notificationBody)
                        ->icon('heroicon-o-circle-stack')
                        ->iconColor($notificationColor)
                        ->sendToDatabase($this->userToNotify);
                } else {
                    BackupSetting::where('instance_id', $this->instance->id)
                        ->update(['deletion_last_run' => now()]);
                }

                Log::info($notificationTitle.' '.$this->getLogBody($inDeletionProcessCount, $allCount, $this->payload, false));
            } else {
                throw new Exception('Course backup '.$this->deletionType.' request for instance: '.$this->instance->name.' failed. Response: '.json_encode($response));
            }
        } catch (Exception $exception) {
            $errorMessage = sprintf(
                'Exception in %s on line %s in method %s: %s',
                $exception->getFile(),
                $exception->getLine(),
                __METHOD__,
                $exception->getMessage()
            );

            if (($this->attempts() >= $this->tries) && $this->isManual) {
                $message = __('An error occured while deleting selected backups.');

                if (isset($this->payload['backups']) && count($this->payload['backups']) <= 3) {
                    $message = __('An error occured while deleting backups: :backupUrls. Check the system logs for more information.', [
                        'backupUrls' => implode(',', array_map(function ($backup) {
                            return $backup['link'];
                        }, $this->payload['backups'])),
                    ]);
                }

                Notification::make()
                    ->danger()
                    ->title(__('Backup deletion error'))
                    ->body($message)
                    ->icon('heroicon-o-circle-stack')
                    ->iconColor('danger')
                    ->sendToDatabase($this->userToNotify);
            }

            Log::error($errorMessage);

            throw $exception;
        }
    }

    /**
     * Check if there are any pending deletion requests for the backups in the payload
     * and remove them from the payload.
     */
    private function removePendingDeletionRequests(): void
    {
        $backupResultIds = array_reduce($this->payload['backups'], function ($carry, $backup) {
            $carry[] = $backup['backup_result_id'];

            return $carry;
        }, []);

        $pendingBackupDeletionResults = BackupResult::whereIn('id', $backupResultIds)
            ->where('in_deletion_process', true)
            ->whereNotNull('moodle_job_id')
            ->get();

        $pendingBackupDeletionResultIds = $pendingBackupDeletionResults->pluck('id')->toArray();
        $backupResultIdsToDelete = array_diff($backupResultIds, $pendingBackupDeletionResultIds);

        $backupsPayloadToDelete = array_filter($this->payload['backups'], function ($backup) use ($backupResultIdsToDelete) {
            return in_array($backup['backup_result_id'], $backupResultIdsToDelete);
        });

        $this->payload['backups'] = $backupsPayloadToDelete;

        $backupsToDeleteCount = count($backupResultIds) - count($pendingBackupDeletionResultIds);
        $allCount = count($backupResultIds);

        $backupUrlsToDelete = BackupResult::whereIn('id', $backupResultIdsToDelete)->get()->pluck('url')->toArray();
        $backupUrlsToSkip = $pendingBackupDeletionResults->pluck('url')->toArray();

        Log::info("There are $backupsToDeleteCount / $allCount backups to be deleted on instance {$this->instance->name} . Backup files to delete: ".json_encode($backupUrlsToDelete).' Some backups might be in deletion process already. Skipping deletion for: '.json_encode($backupUrlsToSkip));
    }

    private function getNotificationColor(int $successful, int $total): string
    {
        if ($successful === 0) {
            return 'danger';
        }

        if ($successful === $total) {
            return 'success';
        }

        return 'warning';
    }

    private function getNotificationTitle(int $successful, int $total): string
    {
        if ($successful === 0) {
            return __('Backup deletion process failed');
        }

        if ($successful === $total) {
            return __('Backup deletion process successful');
        }

        return __('Backup deletion process partially successful');
    }

    private function getLogBody(int $successful, int $total, array $payload, bool $isNotification = false, int $limit = 3): string
    {
        $message = __(':successful out of :total backups are in deletion process on instance :instance.', [
            'successful' => $successful,
            'total' => $total,
            'instance' => $this->instance->short_name,
        ]);

        // Do not print all backup URLs in logs if it's a notification
        if ($isNotification && (count($payload['backups']) > $limit)) {
            return $message.__(' Check system logs for more information.');
        }

        $message .= __(' Backup files to be deleted: :backupUrls', [
            'backupUrls' => implode(', ', array_map(function ($backup) {
                return $backup['link'];
            }, $payload['backups'])),
        ]);

        return $message;
    }
}
