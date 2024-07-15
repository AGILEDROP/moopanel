<?php

namespace App\Http\Controllers\Backup;

use App\Http\Controllers\Controller;
use App\Http\Requests\Backup\BackupRestoreCreate;
use App\Models\BackupResult;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class CourseBackupRestoreController extends Controller
{
    public function restore(BackupRestoreCreate $request, $instance_id): JsonResponse
    {
        $validatedData = $request->validated();
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instance_id)->first();
        $course = $instance->courses()->where('moodle_course_id', $validatedData['courseid'])->first();

        $notificationTitle = $validatedData['status'] ? __('Course backup restore successful') : __('Course backup restore failed');
        $notificationColor = $validatedData['status'] ? 'success' : 'danger';

        try {
            $notificationBody = $validatedData['status'] ?
                __(
                    'Backup restore for course :course on instance :instance was successful with backup file :backup_file.',
                    ['course' => $course->name, 'instance' => $instance->name, 'backup_file' => BackupResult::find($validatedData['backup_result_id'])->url]
                ) :
                __(
                    'Backup restore for course :course on instance :instance failed with backup file :backup_file.',
                    ['course' => $course->name, 'instance' => $instance->name, 'backup_file' => BackupResult::find($validatedData['backup_result_id'])->url]
                );
        } catch (Exception $e) {
            Log::error('Backup restore for course: '.$course->name.' on instance: '.$instance->name.' failed with error: '.$e->getMessage().'.');

            $notificationBody = $validatedData['message'];
        }

        Notification::make()
            ->status($notificationColor)
            ->title($notificationTitle)
            ->body($notificationBody)
            ->icon('heroicon-o-circle-stack')
            ->iconColor($notificationColor)
            ->sendToDatabase(User::find($validatedData['user_id']));

        Log::info('Backup restore for course: '.$course->name.' on instance: '.$instance->name.' received with message: '.$validatedData['message'].'.');

        return response()->json([
            'message' => __('Course backup restore status received successfully.'),
            'status' => true,
        ]);
    }
}
