<?php

namespace App\Jobs\Backup;

use App\Models\BackupResult;
use App\Models\Instance;
use App\Models\User;
use App\Services\ModuleApiService;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class RestoreBackupJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Instance $instance;

    // Has to be max_tries(from config) + 1
    public $tries = 4;

    /**
     * Create a new job instance.
     */
    public function __construct(private BackupResult $backupResult, private string $password, private User $userToNotify)
    {
        $this->instance = $this->backupResult->instance;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $moduleApiService = new ModuleApiService();

        $payload = [
            'storage' => $this->backupResult->backupStorage->storage_key,
            // TODO: version 2.0, external storage credentials
            'credentials' => [],

            'instance_id' => $this->instance->id,
            'moodle_course_id' => $this->backupResult->moodle_course_id,
            'link' => $this->backupResult->url,
            'password' => $this->password,

            'user_id' => $this->userToNotify->id,
            'backup_result_id' => $this->backupResult->id,
        ];

        Log::info('payload: '.json_encode($payload));

        return;

        $response = $moduleApiService->triggerCourseBackupRestore($this->instance->url, Crypt::decrypt($this->instance->api_key), $payload);

        if (! $response->ok()) {

            // Retry job if service is temporarily unavailable
            $maxRetries = config('queue.jobs.course-backup-restore.max_tries') ?? 3;
            if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                Log::info('Retrying course backup restore for instance: '.$this->instance->name.' as the service is temporarily unavailable.');

                $retryAfter = config('queue.jobs.course-backup-restore.retry_after');
                $this->release($retryAfter);

                return;
            }

            Log::error('Course backup restore request for instance failed with status code and body: '.$response->status().' - '.$response->body());

            throw new \Exception('Course backup request failed with status code: '.$response->status().'.');
        }

        $response = $response->json();
        $message = isset($response['message']) ? $response['message'] : 'No message received';

        if (isset($response['status']) && $response['status'] === true) {
            Notification::make()
                ->success()
                ->title(__('Course backups restore in progress.'))
                ->body(__('Backup restore for course :course on instance :instance is in progress. We will notify you, once the action will complete.', ['course' => $this->backupResult->course->name, 'instance' => $this->instance->name]))
                ->icon('heroicon-o-circle-stack')
                ->iconColor('success')
                ->sendToDatabase($this->userToNotify);

            Log::info('Backup restore for course: '.$this->backupResult->course->name.' on instance: '.$this->instance->name.' is in progress. Received response: '.$message);

            return;
        }

        Notification::make()
            ->danger()
            ->title(__('Course backups restore request failed.'))
            ->body(__('Backup restore for course :course on instance :instance failed. Please try again later.', ['course' => $this->backupResult->course->name, 'instance' => $this->instance->name]))
            ->icon('heroicon-o-circle-stack')
            ->iconColor('danger')
            ->sendToDatabase($this->userToNotify);

        Log::error('Backup restore for course: '.$this->backupResult->course->name.' on instance: '.$this->instance->name.' failed. Received response: '.$message);
    }
}
