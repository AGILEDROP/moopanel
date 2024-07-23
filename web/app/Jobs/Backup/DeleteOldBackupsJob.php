<?php

namespace App\Jobs\Backup;

use App\Models\BackupResult;
use App\Models\BackupSetting;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Services\ModuleApiService;
use Exception;
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

    /**
     * Create a new job instance.
     */
    public function __construct(private int $instanceId, private array $payload, private bool $isManual = false)
    {
        //
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
                        $carry[] = $backup['backup_result_id'];
                    }

                    return $carry;
                }, []);

                // Soft delete backup results in mooPanel
                BackupResult::whereIn('id', $successfullyDeletedBackupResultIds)->delete();

                // Update deletion_last_run in backup settings - mark last run
                BackupSetting::where('instance_id', $this->instance->id)
                    ->update(['deletion_last_run' => now()]);

                Log::info(__(
                    'Backup auto-deletion for instance :instance completed. Deleted :countSuccess out of :all requested backups deletion. Response body: :response',
                    [
                        'instance' => $this->instance->name,
                        'countSuccess' => count($successfullyDeletedBackupResultIds),
                        'all' => count($response['backups']),
                        'response' => json_encode($response),
                    ]
                ));
            } else {
                Log::error('Course backup deletion request for instance: '.$this->instance->name.' failed. Response: '.json_encode($response));
            }
        } catch (Exception $exception) {
            $errorMessage = sprintf(
                'Exception in %s on line %s in method %s: %s',
                $exception->getFile(),
                $exception->getLine(),
                __METHOD__,
                $exception->getMessage()
            );

            Log::error($errorMessage);
        }
    }
}
