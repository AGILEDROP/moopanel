<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\CourseBackupCreate;
use App\Models\BackupResult;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use Exception;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class CourseBackupController extends Controller
{

    /**
     * Handle incoming course backup status
     *
     * @param  CourseBackupCreate $request
     * @param  mixed $instance_id
     * @return JsonResponse
     */
    public function store(CourseBackupCreate $request, $instance_id): JsonResponse
    {
        $validatedData = $request->validated();
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instance_id)->first();
        $isSuccessfull = true;

        // Avoid duplicated notifications if request repeated for already successful update
        if ($this->hasPendingBackupRequest($instance, $validatedData['courseid'])) {
            $isSuccessfull = $isSuccessfull && $this->statusUpdate($instance, $validatedData);
        }

        if (!$isSuccessfull) {
            return response()->json([
                'message' => 'There was and error while receiving and updating course backup results. Please try again or contact support.',
                'status' => false,
            ], 500);
        }

        return response()->json([
            'message' => 'Course backup statuses received successfully.',
            'status' => true,
        ]);
    }


    /**
     * Check if there still exists backup result for instance:course that has pending status
     *
     * @param  Instance $instance
     * @param  int $courseId
     * @return bool
     */
    private function hasPendingBackupRequest(Instance $instance, int $courseId): bool
    {
        return $instance
            ->backup_results()
            ->where('moodle_course_id', $courseId)
            ->where('status', BackupResult::STATUS_PENDING)
            ->exists();
    }

    /**
     * Update the status of all the backups results for instance:course pair
     *
     * @param  Instance $instance
     * @param  array $data
     * @return bool
     */
    private function statusUpdate(Instance $instance, array $data): bool
    {
        $status = true;

        try {

            $updatedBackupResultIds = BackupResult::where('moodle_course_id', $data['courseid'])
                ->where('instance_id', $instance->id)
                ->where('status', BackupResult::STATUS_PENDING)
                ->pluck('id')
                ->toArray();

            BackupResult::whereIn('id', $updatedBackupResultIds)
                ->update([
                    'status' => $data['status'] ? BackupResult::STATUS_SUCCESS : BackupResult::STATUS_FAILED,
                    'message' => $data['status'] ? __('Backup created successfully') : __('Backup creation failed'),
                    'url' => $data['link'],
                    'password' => $data['password'],
                ]);

            $updatedBackupResults = BackupResult::whereIn('id', $updatedBackupResultIds)->get();

            foreach ($updatedBackupResults as $backupResult) {

                // Check if this backup request was manually triggered and if all backup results for this timestamp are resolved
                if (!is_null($backupResult->manual_trigger_timestamp)) {

                    $allBackupResultsForTimestamp = BackupResult::where('manual_trigger_timestamp', $backupResult->manual_trigger_timestamp)
                        ->get();

                    if (count($allBackupResultsForTimestamp) > 0) {

                        // Check if all backup results with this timestamp have non-pending status
                        $allBackupResultsForTimestampResolved = $allBackupResultsForTimestamp->reduce(function (?bool $carry, BackupResult $item) {
                            $isStatusSet = !is_null($item->status);
                            return $carry && $isStatusSet;
                        }, true);

                        // Notify user if all backups requests in his batch are resolved
                        if ($allBackupResultsForTimestampResolved && !is_null($backupResult->user_id)) {
                            $this->notifyUser($backupResult->user, $allBackupResultsForTimestamp);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error(__FILE__ . ':' . __LINE__ . ' - ' . " Failed to update backup result statuses for instance {$instance->id} and course {$data['courseid']}." . " Message: " . $e->getMessage());

            $status = false;
        }

        return $status;
    }

    /**
     * Notify the user about the status of the backup results
     *
     * @param  Instance $instance
     * @param  array $validatedData
     * @return void
     */
    private function notifyUser(User $user, Collection $backupResults): void
    {
        $successfullBackups = $this->getSuccessfullBackupsCount($backupResults);
        $allBackups = count($backupResults);
        $status = $this->getResponseStatus($successfullBackups, $allBackups);
        $message = $this->getResponseMessage($successfullBackups, $allBackups);

        $instanceIds = $backupResults->pluck('instance_id')->unique()->toArray();
        $instances = Instance::withoutGlobalScope(InstanceScope::class)->whereIn('id', $instanceIds)->get();
        $instanceShortNames = $instances->pluck('short_name')->toArray();

        Notification::make()
            ->status($status)
            ->title(__($message))
            ->body(__(':count course backups on instances :instances have been created. Go to backup results for more details', ['instances' => implode(',', $instanceShortNames), 'count' => $successfullBackups . '/' . $allBackups]))
            ->actions([
                Action::make('view')
                    ->color($status)
                    ->button()
                    ->url(function () use ($instances) {
                        if (count($instances) == 1) {
                            return route('filament.app.backups.resources.backup-results.index', ['tenant' => $instances->first()]);
                        }

                        return route('filament.admin.resources.instances.index');
                    }),
                Action::make('cancel')
                    ->color('secondary')
                    ->close(),
            ])
            ->icon('heroicon-o-circle-stack')
            ->iconColor($status)
            ->sendToDatabase($user);
    }

    /**
     * Returns the number of successful backups
     *
     * @param  Collection  $backupResults
     */
    private function getSuccessfullBackupsCount(Collection $backupResults): int
    {
        return $backupResults->reduce(function (int $carry, BackupResult $item) {
            return $carry + ((isset($item['status']) && $item['status']) ? 1 : 0);
        }, 0);
    }

    /**
     * Returns the response status based on the number of successful updates
     *
     * @param  mixed  $successfulUpdates
     * @param  mixed  $allUpdates
     */
    private function getResponseStatus(int $successfulUpdates, int $allUpdates): string
    {
        $status = 'success';

        if ($successfulUpdates === 0) {
            $status = 'danger';
        } elseif ($successfulUpdates < $allUpdates) {
            $status = 'warning';
        }

        return $status;
    }

    /**
     * Returns the response message based on the number of successful updates
     *
     * @param  mixed  $successfulUpdates
     * @param  mixed  $allUpdates
     */
    private function getResponseMessage(int $successfulUpdates, int $allUpdates): string
    {
        $message = __('Course backups created successfully');

        if ($successfulUpdates === 0) {
            $message = __('Course backups failed');
        } elseif ($successfulUpdates < $allUpdates) {
            $message = __('Course backup generation partially succeeded');
        }

        return $message;
    }
}
