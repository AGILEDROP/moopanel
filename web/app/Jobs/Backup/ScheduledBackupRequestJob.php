<?php

namespace App\Jobs\Backup;

use App\Models\BackupSetting;
use App\Models\Course;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ScheduledBackupRequestJob implements ShouldQueue
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
        $instances = Instance::withoutGlobalScope(InstanceScope::class)
            ->select('instances.*')
            ->join('backup_settings', 'instances.id', '=', 'backup_settings.instance_id')
            ->where('auto_backups_enabled', true)
            ->where(function ($query) {
                $query->whereNull('backup_last_run')
                    ->orWhereRaw('backup_last_run < NOW() - (backup_interval || \' hours\')::interval');
            })
            ->get();

        foreach ($instances as $instance) {

            $coursesToBackup = Course::where('instance_id', $instance->id)
                ->where('is_scheduled', 1)
                ->get();

            $additionalTempCourseData = [];
            foreach ($coursesToBackup as $course) {
                $additionalTempCourseData[] = [
                    'moodle_course_id' => $course->moodle_course_id,
                    'course_id' => $course->id,
                ];
            }

            $payload = [
                'instance_id' => $instance->id,

                // TODO: add dnynamic storage info - maybe from settings
                'storage' => 'local',
                'credentials' => [
                    'url' => 'https://test-link-for-storage.com/folder',
                    'api-key' => 'abcd1234',
                ],

                // Request backup only for courses that belong to current instance
                'courses' => $coursesToBackup->pluck('moodle_course_id')->toArray(),
                'temp' => $additionalTempCourseData,
            ];

            BackupSetting::where('instance_id', $instance->id)
                ->update(['backup_last_run' => now()]);

            BackupRequestJob::dispatch(null, $payload, false);

            Log::info('Scheduled backup request for instance '.$instance->name.' for moodle_job_id-s: '.implode(', ', $coursesToBackup->pluck('moodle_course_id')->toArray()).'.');
        }
    }
}
