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
        $this->deletionType = $isManual ? 'manual-deletion' : 'auto-deletion';
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (! isset($this->payload['backups']) || empty($this->payload['backups'])) {
            Log::info('No course-backups for instanceID: '.$this->payload['instance_id'].' specified. Aborting backup deletion request.');

            return;
        }

        try {
            $this->instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->instanceId);

            $moduleApiService = new ModuleApiService();

            $response = $moduleApiService->triggerCourseBackupDeletion($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (! $response->ok()) {

                // Retry job if service is temporarily unavailable
                $maxRetries = config('queue.jobs.course-backup-deletion.max_tries') ?? 3;
                if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                    Log::info('Retrying course backup deletions for instance: '.$this->instance->name.' as the service is temporarily unavailable.');

                    $retryAfter = config('queue.jobs.course-backup-deletion.retry_after');
                    $this->release($retryAfter);

                    return;
                }

                Log::error('Course backup request for instance failed with status code and body: '.$response->status().' - '.$response->body());

                throw new \Exception('Course backup request failed with status code: '.$response->status().'.');
            }

            $response = $response->json();

            if (isset($response['backups']) && $response['backups']) {

                $successfullyDeletedBackupResultIds = array_reduce($response['backups'], function ($carry, $backup) {
                    if ($backup['status']) {
                        $carry[] = (int) $backup['backup_result_id'];
                    }

                    return $carry;
                }, []);

                // Manually deleting single course backup
                if ($this->isManual) {
                    $backupResultId = $response['backups'][0]['backup_result_id'];
                    $backupResultStatus = $response['backups'][0]['status'];
                    $backupResultMessage = $response['backups'][0]['message'];

                    $backupResult = BackupResult::findOrFail($backupResultId);

                    // Failed manual backup deletion of single course
                    if (! is_null($backupResultStatus) && $backupResultStatus === false) {
                        $message = __('Failed to delete backup for course :course with filename :filename on instance :instance with message - :message', [
                            'course' => $backupResult->course->name,
                            'filename' => $backupResult->url,
                            'instance' => $this->instance->short_name,
                            'message' => $backupResultMessage,
                        ]);

                        Notification::make()
                            ->danger()
                            ->title(__('Backup deletion failed'))
                            ->body($message)
                            ->icon('heroicon-o-circle-stack')
                            ->iconColor('danger')
                            ->sendToDatabase($this->userToNotify);

                        Log::info($message);

                        return;
                    }

                    Notification::make()
                        ->success()
                        ->title(__('Backup deletion completed'))
                        ->body(__('Successfully deleted backup for course :course with filename :filename on instance :instance.', [
                            'course' => $backupResult->course->name,
                            'filename' => $backupResult->url,
                            'instance' => $this->instance->short_name,
                        ]))
                        ->icon('heroicon-o-circle-stack')
                        ->iconColor('success')
                        ->sendToDatabase($this->userToNotify);
                }

                // Soft delete backup results in mooPanel
                BackupResult::whereIn('id', $successfullyDeletedBackupResultIds)->delete();

                // Update deletion_last_run in backup settings - mark last run only on auto-backup deletion
                if (! $this->isManual) {
                    BackupSetting::where('instance_id', $this->instance->id)
                        ->update(['deletion_last_run' => now()]);
                }

                // General log on successful deletion
                Log::info(__(
                    'Backup :deletion_type for instance :instance completed. Deleted :countSuccess out of :all requested backups deletion. Response body: :response',
                    [
                        'deletion_type' => $this->deletionType,
                        'instance' => $this->instance->name,
                        'countSuccess' => count($successfullyDeletedBackupResultIds),
                        'all' => count($response['backups']),
                        'response' => json_encode($response),
                    ]
                ));
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
                $backupResultId = $this->payload['backups'][0]['backup_result_id'];
                $backupResult = BackupResult::find($backupResultId);

                $message = __('Failed to delete selected backup. Check the system logs for more information.');
                if ($backupResult) {
                    $this->instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->instanceId);

                    $message = __('Failed to delete backup for course :course with filename :filename on instance :instance.', [
                        'course' => $backupResult->course->name,
                        'filename' => $backupResult->url,
                        'instance' => $this->instance->short_name,
                    ]);
                }

                Notification::make()
                    ->danger()
                    ->title(__('Backup deletion failed'))
                    ->body($message)
                    ->icon('heroicon-o-circle-stack')
                    ->iconColor('danger')
                    ->sendToDatabase($this->userToNotify);
            }

            Log::error($errorMessage);

            throw $exception;
        }
    }
}
