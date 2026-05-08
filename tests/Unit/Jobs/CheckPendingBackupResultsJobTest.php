<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Backup\CheckPendingBackupResultsJob;
use App\Models\BackupResult;
use App\Models\Course;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;

class CheckPendingBackupResultsJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasColumn('instances', 'short_name')) {
            Schema::table('instances', function (Blueprint $table) {
                $table->string('short_name')->nullable();
            });
        }
    }

    public function test_backup_checker_does_not_update_create_backup_on_success_check(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => BackupResult::CHECK_STATUS_SUCCESS,
            ], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $course = Course::factory()->for($instance)->create(['name' => 'Math']);
        $backupResult = BackupResult::factory()->forCourse($course)->pending()->create([
            'moodle_job_id' => 10,
            'message' => null,
        ]);

        $job = new CheckPendingBackupResultsJob($backupResult, BackupResult::JOB_KEY_CREATE);
        $job->handle();

        $backupResult->refresh();

        $this->assertNull($backupResult->status);
        $this->assertNull($backupResult->message);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_backup_checker_soft_deletes_backup_on_successful_delete(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => BackupResult::CHECK_STATUS_SUCCESS,
            ], 200),
        ]);

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $course = Course::factory()->for($instance)->create(['name' => 'Math']);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inDeletionProcess($user->id)->create([
            'moodle_job_id' => 11,
            'url' => 'https://files/delete.mbz',
        ]);

        $job = new CheckPendingBackupResultsJob($backupResult, BackupResult::JOB_KEY_DELETE);
        $job->handle();

        $this->assertSoftDeleted('backup_results', ['id' => $backupResult->id]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_backup_checker_keeps_restore_pending_state_when_remote_status_is_pending(): void
    {
        Http::fake([
            '*' => Http::response([
                'status' => BackupResult::CHECK_STATUS_PENDING,
            ], 200),
        ]);

        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $instance->update(['short_name' => 'demo']);
        $course = Course::factory()->for($instance)->create(['name' => 'Math']);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inRestoreProcess()->create([
            'user_id' => $user->id,
            'moodle_job_id' => 12,
        ]);

        $job = new CheckPendingBackupResultsJob($backupResult, BackupResult::JOB_KEY_RESTORE);
        $job->handle();

        $backupResult->refresh();

        $this->assertTrue($backupResult->in_restore_process);
        $this->assertSame(12, $backupResult->moodle_job_id);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_backup_checker_throws_on_invalid_response_body(): void
    {
        $this->expectException(\Exception::class);

        Http::fake([
            '*' => Http::response([
                'no_status' => true,
            ], 200),
        ]);

        $instance = Instance::factory()->withApiKey('secret-token')->create([
            'url' => 'https://example.test',
        ]);
        $course = Course::factory()->for($instance)->create();
        $backupResult = BackupResult::factory()->forCourse($course)->pending()->create([
            'moodle_job_id' => 13,
        ]);

        $job = new CheckPendingBackupResultsJob($backupResult, BackupResult::JOB_KEY_CREATE);
        $job->handle();
    }
}
