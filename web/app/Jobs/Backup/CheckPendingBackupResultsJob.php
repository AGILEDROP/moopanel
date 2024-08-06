<?php

namespace App\Jobs\Backup;

use App\Models\BackupResult;
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

class CheckPendingBackupResultsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;

    private Instance $instance;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public BackupResult $backupResult, public string $type)
    {
        $this->instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->backupResult->instance_id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $moduleApiService = new ModuleApiService();
            $moodleJobId = $this->backupResult->moodle_job_id;

            $this->payload = [
                'id' => $moodleJobId,
                'type' => $this->type,
            ];

            $response = $moduleApiService->triggerTaskCheck($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (! $response->ok()) {
                Log::error(__(
                    'Backup scheduled check of type :type and instance :instance failed with status code: :status.',
                    [
                        'type' => $this->type,
                        'instance' => $this->instance->name,
                        'status' => $response->status(),
                    ]
                ));

                throw new \Exception("Backup check of type {$this->type} failed with status code: ".$response->status());
            }

            $body = $response->json();

            if (! array_key_exists('status', $body)) {
                Log::error(__(
                    'Invalid response body for backup check of type :type and instance :instance. Missing response status. Received response body: :body',
                    [
                        'type' => $this->type,
                        'instance' => $this->instance->name,
                        'body' => json_encode($body),
                    ]
                ));

                throw new \Exception("Invalid response body for backup check of type {$this->type} and instance {$this->instance->name}.");
            }

            $this->updateBackupResult($body);

            if (! is_null($this->backupResult->user_id)) {
                $this->notify($body, $this->backupResult->user);
            }

            $this->logStatus($body);
        } catch (\Exception $exception) {
            Log::error(__METHOD__.' line: '.__LINE__.' - '."Failed to request for backup status check for instance: {$this->instance->name} and backup_result_id {$this->backupResult->id}". ' Error message: '.$exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Update or delete the backup result
     */
    private function updateBackupResult(array $data): void
    {
        $status = $this->parseStatus($data['status']);
        $error = $data['error'] ?? '';

        switch ($this->type) {
            case BackupResult::JOB_KEY_CREATE:
                $this->backupResult->update([
                    'status' => $status,
                    'message' => $error,
                ]);

                break;
            case BackupResult::JOB_KEY_DELETE:
                $this->backupResult->update([
                    'in_deletion_process' => is_null($status) ? true : false,
                    'moodle_job_id' => is_null($status) ? $this->backupResult->moodle_job_id : null,
                    'message' => $error,
                ]);

                if ($status === true) {
                    $this->backupResult->delete();
                }
                break;
        }
    }

    /**
     * Notify user about the status of the backup
     */
    private function notify(array $data, User $user): void
    {
        $status = $this->parseStatus($data['status']);

        // Do not notify user yet, if the process is still pending
        if (is_null($status)) {
            return;
        }

        $statusColor = $this->getStatusColor($status);

        $notificationTitle = $this->getNotificationTitle($status);
        $notificationBody = $this->getNotificationBody($status, $data['error'] ?? '');

        Notification::make()
            ->status($statusColor)
            ->title($notificationTitle)
            ->body($notificationBody)
            ->icon('heroicon-o-circle-stack')
            ->iconColor($statusColor)
            ->sendToDatabase($user);
    }

    /**
     * Log the status of the backup
     */
    private function logStatus(array $data): void
    {
        $status = $this->parseStatus($data['status']);

        $notificationTitle = $this->getNotificationTitle($status);
        $notificationBody = $this->getNotificationBody($status, $data['error'] ?? '');

        Log::info($notificationTitle.'- Via backup checker job - '.$notificationBody);
    }

    /**
     * Get the count of successful updates
     *
     * @param  array  $validatedData
     * @return int
     */
    private function getNotificationTitle(?bool $status): string
    {
        switch ($this->type) {
            case BackupResult::JOB_KEY_CREATE:
                return match ($status) {
                    true => __('Backup creation successful!'),
                    false => __('Backup creation failed!'),
                    default => __('Backup creation in progress.'),
                };
            case BackupResult::JOB_KEY_DELETE:
                return match ($status) {
                    true => __('Backup deletion successful!'),
                    false => __('Backup deletion failed!'),
                    default => __('Backup deletion in progress.'),
                };
            default:
                Log::warning(__FILE__.__METHOD__.'Unknown backup result type: '.$this->type.' for instance: '.$this->instance->name);

                return __('Backup operation in progress.');
        }
    }

    /**
     * Get notification body
     *
     * @param  ?bool  $status
     */
    private function getNotificationBody(?bool $status, string $error): string
    {
        switch ($this->type) {
            case BackupResult::JOB_KEY_CREATE:
                return match ($status) {
                    true => __(
                        'Backup creation for course :course on :instance was successful, generating backup file :file',
                        [
                            'course' => $this->backupResult->course->name,
                            'instance' => $this->instance->short_name,
                            'file' => $this->backupResult->url,
                        ]
                    ),
                    false => __(
                        'Backup creation for course :course on :instance failed with error :error',
                        [
                            'course' => $this->backupResult->course->name,
                            'instance' => $this->instance->short_name,
                            'error' => $error,
                        ]
                    ),
                    default => __(
                        'Backup creation for course :course on :instance in progress',
                        [
                            'course' => $this->backupResult->course->name,
                            'instance' => $this->instance->short_name,
                        ]
                    ),
                };
            case BackupResult::JOB_KEY_DELETE:
                return match ($status) {
                    true => __(
                        'Backup deletion for course :course on :instance was successful. Deleted file :file',
                        [
                            'course' => $this->backupResult->course->name,
                            'instance' => $this->instance->short_name,
                            'file' => $this->backupResult->url,
                        ]
                    ),
                    false => __(
                        'Backup deletion for course :course on :instance failed with error :error',
                        [
                            'course' => $this->backupResult->course->name,
                            'instance' => $this->instance->short_name,
                            'error' => $error,
                        ]
                    ),
                    default => __(
                        'Backup deletion for course :course on :instance in progress',
                        [
                            'course' => $this->backupResult->course->name,
                            'instance' => $this->instance->short_name,
                        ]
                    ),
                };
            default:
                Log::warning(__FILE__.__METHOD__.'Unknown backup result type: '.$this->type.' for instance: '.$this->instance->name);

                return __('Backup operation in progress.');
        }
    }

    /**
     * Parse status from int to bool
     */
    private function parseStatus(int $status): ?bool
    {
        switch ($status) {
            case 1:
                return true;
            case 2:
                return null;
            case 3:
                return false;
            default:
                return null;
        }
    }

    /**
     * Returns the color of the status
     *
     * @param  mixed  $successfulUpdates
     * @param  mixed  $allUpdates
     */
    private function getStatusColor(?bool $status): string
    {
        return match ($status) {
            true => 'success',
            false => 'danger',
            default => 'warning',
        };
    }
}
