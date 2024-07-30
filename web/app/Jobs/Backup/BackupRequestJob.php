<?php

namespace App\Jobs\Backup;

use App\Jobs\ModuleApi\Sync;
use App\Models\BackupResult;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\CourseSyncType;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class BackupRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Indicates if there is existing backup request for at least one of the courses on backup request list
     */
    private bool $pendingCourseBackupsExist = false;

    // Has to be max_tries(from config) + 1
    public $tries = 4;

    private Instance $instance;

    /**
     * Create a new job instance.
     */
    public function __construct(private ?User $userToNotify, private array $payload, private bool $isManual = true)
    {
        $this->instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->payload['instance_id']);
        $this->removeCoursesFromPayload();
        $this->createPendingBackupResults();
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (empty($this->payload['courses'])) {
            Log::info('No courses to backup for instanceID: '.$this->payload['instance_id'].' Aborting backup request.');

            return;
        }

        try {
            $moduleApiService = new ModuleApiService();

            $response = $moduleApiService->triggerCourseBackup($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (! $response->ok()) {

                // Retry job if service is temporarily unavailable
                $maxRetries = config('queue.jobs.course-backup.max_tries') ?? 3;
                if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                    Log::info('Retrying course backup for instance: '.$this->instance->name.' as the service is temporarily unavailable.');

                    $retryAfter = config('queue.jobs.course-backup.retry_after');
                    $this->release($retryAfter);

                    return;
                }

                Log::error('Course backup request for instance failed with status code and body: '.$response->status().' - '.$response->body());

                throw new \Exception('Course backup request failed with status code: '.$response->status().'.');
            }

            $response = $response->json();

            // There were some immediate errors on the Moodle side - some backup jobs didnt proceed on moodle
            if (isset($response['backups']) && (count($response['backups']) != count($this->payload['courses']))) {

                // Notify user about failed backups
                if ($this->isManual && ! is_null($this->userToNotify)) {
                    Notification::make()
                        ->warning()
                        ->title(__('Some course backups failed!'))
                        ->body(__('Some course backups for instance :instance failed or dont have any changes. Check backup report to see the failed backups.', ['instance' => $this->instance->name]))
                        ->icon('heroicon-o-circle-stack')
                        ->iconColor('warning')
                        ->actions([
                            Action::make('view')
                                ->color('warning')
                                ->button()
                                ->url(route('filament.app.backups.resources.backup-results.index', ['tenant' => $this->instance])),
                            Action::make('cancel')
                                ->color('secondary')
                                ->close(),
                        ])
                        ->sendToDatabase($this->userToNotify);

                    $this->markFailedBackups($response);
                } else {
                    // Log failed backups on auto backups
                    Log::error('Partial backup success for instance: '.$this->instance->name.'. Check backup report to see the failed backups. Some courses might not have any changes and dont need backup. Response body: '.json_encode($response));
                }

                // Run course sync to delete coureses that might be deleted on moodle instance
                // NOTE: currently only notify user about this option
                // Sync::dispatch($this->instance, CourseSyncType::TYPE, 'Course sync failed.');
            }

            if ($this->isManual && ! is_null($this->userToNotify)) {
                Notification::make()
                    ->success()
                    ->title(__('Course backups in progress.'))
                    ->body(__('Course backups(:count) for instance :instance are in progress. We will notify you once the backups are completed.', ['count' => count($this->payload['courses']), 'instance' => $this->instance->name]))
                    ->icon('heroicon-o-circle-stack')
                    ->iconColor('success')
                    ->sendToDatabase($this->userToNotify);
            } else {
                Log::info('Course backups(:count) for instance :instance are in progress.', ['count' => count($this->payload['courses']), 'instance' => $this->instance->name]);
            }
        } catch (\Exception $exception) {
            $errorMessage = sprintf(
                'Exception in %s on line %s in method %s: %s',
                $exception->getFile(),
                $exception->getLine(),
                __METHOD__,
                $exception->getMessage()
            );

            Log::error($errorMessage);

            if ($this->attempts() >= ($this->tries) && $this->isManual && ! is_null($this->userToNotify)) {
                Notification::make()
                    ->danger()
                    ->title(__('Course backups failed!'))
                    ->body(__('Failed to request for course backups on instance :instance. Please contact administrator for more information.', ['instance' => $this->instance->name]))
                    ->icon('heroicon-o-circle-stack')
                    ->iconColor('danger')
                    ->sendToDatabase($this->userToNotify);
            }

            throw $exception;
        }
    }

    /**
     * Remove courses from payload if there exist pending backup request for this course(and its instance)
     */
    private function removeCoursesFromPayload(): void
    {
        // Moodle course IDs of courses that already have pending backup requests
        $pendingBackupResults = BackupResult::where('status', BackupResult::STATUS_PENDING)
            ->where('instance_id', $this->payload['instance_id'])
            ->whereIn('moodle_course_id', $this->payload['courses'])
            ->get()
            ->pluck('moodle_course_id')
            ->unique()
            ->toArray();

        // Remove those Moodle Course IDs from payload
        $filter = array_filter($this->payload['courses'], fn ($courseId) => ! in_array($courseId, $pendingBackupResults));
        $nonPendingCourses = array_values($filter);

        // Some of the requested courses are already in progress
        if (count($nonPendingCourses) != count($this->payload['courses'])) {
            if ($this->isManual && ! is_null($this->userToNotify)) {

                Notification::make()
                    ->info()
                    ->title(__('Course backups already in progress.'))
                    ->body(__('Some course backups from current request are already in progress for instance :instance. Please wait for backups to resolve. We will notify you, once the backups are complete. You can see the the pending backups on the backup results list.', ['instance' => $this->instance->name]))
                    ->icon('heroicon-o-circle-stack')
                    ->actions([
                        Action::make('view')
                            ->color('info')
                            ->button()
                            ->url(route('filament.app.backups.resources.backup-results.index', ['tenant' => $this->instance])),
                        Action::make('cancel')
                            ->color('secondary')
                            ->close(),
                    ])
                    ->iconColor('info')
                    ->sendToDatabase($this->userToNotify);
            } else {
                Log::info('Some courses are already in progress for instance: '.$this->payload['instance_id'].'. We will skip these courses and backup the rest. Check those in backup_results.');
            }
        }

        $this->payload['courses'] = $nonPendingCourses;
    }

    /**
     * Create pending backup results for each course
     * Create item in backup_result for instance:course also in case when there already exist backup request for this course
     */
    private function createPendingBackupResults(): void
    {
        $currentTimestamp = now()->timestamp;

        foreach ($this->payload['temp'] as $courseIds) {

            // User can only have one manual pending backup result for instance:course in the moment
            // It doesnt make sense to backup n identical courses n times in a row without receiving response first
            // backup results will be poluted with duplicated entries and list wont be readable
            // NOTE: duplicated pending backup course IDs are not sent to moodle on the previous step(removed from "courses" array in payload)
            $manualBackupConditions =
                $this->isManual &&
                ! is_null($this->userToNotify) &&
                BackupResult::where('instance_id', $this->payload['instance_id'])
                    ->where('moodle_course_id', $courseIds['moodle_course_id'])
                    ->where('status', BackupResult::STATUS_PENDING)
                    ->where('manual_trigger_timestamp', '!=', null)
                    ->where('user_id', $this->userToNotify->id)
                    ->exists();

            // There can be only one auto backup pending result for instance:course in the moment
            $autoBackupConditions = BackupResult::where('instance_id', $this->payload['instance_id'])
                ->where('moodle_course_id', $courseIds['moodle_course_id'])
                ->where('status', BackupResult::STATUS_PENDING)
                ->whereNull('manual_trigger_timestamp')
                ->whereNull('user_id')
                ->exists();

            if ($manualBackupConditions || $autoBackupConditions) {
                $this->pendingCourseBackupsExist = true;

                continue;
            }

            $currentActiveBackupStorage = $this->instance->backupStorages()->where('active', 1)->first();

            BackupResult::create([
                'instance_id' => $this->payload['instance_id'],
                'course_id' => $courseIds['course_id'],
                'moodle_course_id' => $courseIds['moodle_course_id'],
                'manual_trigger_timestamp' => $this->isManual ? $currentTimestamp : null,
                'user_id' => $this->userToNotify?->id,
                'status' => BackupResult::STATUS_PENDING,
                'url' => null,
                'password' => null,
                'message' => null,
                'backup_storage_id' => $currentActiveBackupStorage?->id,
            ]);
        }

        unset($this->payload['temp']);

        Log::info('Created pending backup results for instanceID: '.$this->payload['instance_id'].' and courses: '.json_encode($this->payload['courses']));
    }

    private function markFailedBackups(array $response): void
    {
        $failedBackups = isset($response['errors']) ? $response['errors'] : [];

        foreach ($failedBackups as $backup) {
            BackupResult::where('instance_id', $this->instance->id)
                ->where('moodle_course_id', $backup['id'])
                ->where('status', BackupResult::STATUS_PENDING)
                ->update([
                    'status' => BackupResult::STATUS_FAILED,
                    'message' => $backup['message'],
                ]);
        }
    }
}
