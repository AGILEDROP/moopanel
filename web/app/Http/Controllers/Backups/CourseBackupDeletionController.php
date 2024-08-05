<?php

namespace App\Http\Controllers\Backups;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\BackupDeletionCreate;
use App\Models\BackupResult;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CourseBackupDeletionController extends Controller
{
    public function delete(BackupDeletionCreate $request, int $instance_id): JsonResponse
    {
        $validatedData = $request->validated();
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instance_id)->first();
        $isSuccessfull = true;

        // Avoid duplicated notifications if request repeated for already successful update
        try {
            $isSuccessfull = $isSuccessfull && $this->statusUpdate($instance, $validatedData);
        } catch (Exception $e) {
            Log::error(__FILE__ . ':' . __LINE__ . ' - ' . " Failed to check if there is pending backup deletion request for instance {$instance->id} and backup-id {$validatedData['backup_result_id']}. on backup-deletion endpoint" . ' Message: ' . $e->getMessage());
            $isSuccessfull = false;
        }

        if (!$isSuccessfull) {
            return response()->json([
                'message' => 'There was and error while receiving and updating course backup deletion status. Please try again or contact support.',
                'status' => false,
            ], 500);
        }

        return response()->json([
            'message' => 'Course backup deletion status received successfully.',
            'status' => true,
        ]);
    }

    /**
     * Update the status of all the backups results for instance:course pair
     */
    private function statusUpdate(Instance $instance, array $data): bool
    {
        $statusUpdateSuccessfull = true;

        try {
            $backupResult = BackupResult::findOrFail($data['backup_result_id']);
            $courseName = $backupResult->course->name;
            $status = $data['status'];
            $message = $data['message'];
            $backupType = is_null($data['user_id']) ? 'auto' : 'manual';
            $statusText = $status ? 'success' : 'failed';
            $notificationType = $status ? 'success' : 'danger';

            $notificationTitle = __('Backup deletion :status', ['status' => $statusText]);
            $notificationBody = __('Backup deletion :status for course :course with filename :filename on instance :instance with message :message', [
                'status' => $statusText,
                'course' => $courseName,
                'filename' => $backupResult->url,
                'instance' => $instance->short_name,
                'message' => $message,
            ]);

            // Notify user on manual deletion
            if (!is_null($data['user_id'])) {
                Notification::make()
                    ->status($notificationType)
                    ->title($notificationTitle)
                    ->body($notificationBody)
                    ->icon('heroicon-o-circle-stack')
                    ->iconColor($notificationType)
                    ->sendToDatabase(User::findOrFail($data['user_id']));
            }

            $backupResult->update([
                'in_deletion_process' => false,
                'moodle_job_id' => null,
            ]);

            if ($status) {
                $backupResult->delete();
            }

            // General log on successful deletion
            Log::info($notificationBody . ' Response body: ' . json_encode($data));
        } catch (Exception $e) {
            Log::error(__FILE__ . ':' . __LINE__ . ' - ' . " Failed to update backup result statuses for instance {$instance->id} and backup_result_id {$data['backup_result_id']}. on backup-deletion endpoint" . ' Message: ' . $e->getMessage());
            $statusUpdateSuccessfull = false;
        }

        return $statusUpdateSuccessfull;
    }
}
