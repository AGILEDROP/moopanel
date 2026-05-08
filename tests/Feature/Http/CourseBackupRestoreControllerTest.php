<?php

namespace Tests\Feature\Http;

use App\Models\BackupResult;
use App\Models\Course;
use App\Models\Instance;
use App\Models\User;
use Tests\TestCase;

class CourseBackupRestoreControllerTest extends TestCase
{
    public function test_restore_clears_in_process_flags_and_sends_notification(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 808, 'name' => 'Physics']);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inRestoreProcess()->create([
            'user_id' => $user->id,
            'url' => 'https://files/restore.mbz',
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/restore/instance/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'status' => true,
                'message' => 'restored',
                'user_id' => $user->id,
                'backup_result_id' => $backupResult->id,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('backup_results', [
            'id' => $backupResult->id,
            'in_restore_process' => 0,
            'moodle_job_id' => null,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_restore_returns_error_when_backup_is_not_in_restore_process(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 809]);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->create([
            'user_id' => $user->id,
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/restore/instance/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'status' => false,
                'message' => 'nope',
                'user_id' => $user->id,
                'backup_result_id' => $backupResult->id,
            ])
            ->assertStatus(500)
            ->assertJson(['status' => false]);
    }

    public function test_restore_validates_course_belongs_to_instance(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $otherCourse = Course::factory()->create();
        $backupResult = BackupResult::factory()->forCourse($otherCourse)->inRestoreProcess()->create([
            'user_id' => $user->id,
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/restore/instance/{$instance->id}", [
                'courseid' => $otherCourse->moodle_course_id,
                'status' => true,
                'message' => 'restored',
                'user_id' => $user->id,
                'backup_result_id' => $backupResult->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['courseid']);
    }

    public function test_failed_restore_emits_danger_notification_and_clears_flags(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 810, 'name' => 'Chemistry']);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inRestoreProcess()->create([
            'user_id' => $user->id,
            'url' => 'https://files/restore-fail.mbz',
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/restore/instance/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'status' => false,
                'message' => 'restore failed',
                'user_id' => $user->id,
                'backup_result_id' => $backupResult->id,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('backup_results', [
            'id' => $backupResult->id,
            'in_restore_process' => 0,
            'moodle_job_id' => null,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }
}
