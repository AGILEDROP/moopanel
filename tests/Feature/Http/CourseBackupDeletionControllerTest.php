<?php

namespace Tests\Feature\Http;

use App\Models\BackupResult;
use App\Models\Course;
use App\Models\Instance;
use App\Models\User;
use Tests\TestCase;

class CourseBackupDeletionControllerTest extends TestCase
{
    public function test_successful_manual_deletion_clears_flags_soft_deletes_and_notifies(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['name' => 'Math']);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inDeletionProcess($user->id)->create([
            'url' => 'https://files/delete.mbz',
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/delete/instance/{$instance->id}", [
                'user_id' => $user->id,
                'backup_result_id' => $backupResult->id,
                'status' => true,
                'message' => 'deleted',
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertSoftDeleted('backup_results', ['id' => $backupResult->id]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_failed_automatic_deletion_clears_flags_without_notification_or_delete(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['name' => 'Biology']);
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inDeletionProcess(null)->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/delete/instance/{$instance->id}", [
                'user_id' => null,
                'backup_result_id' => $backupResult->id,
                'status' => false,
                'message' => 'failed',
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('backup_results', [
            'id' => $backupResult->id,
            'in_deletion_process' => 0,
            'moodle_job_id' => null,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_deletion_validation_rejects_non_pending_backup_result(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create();
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/delete/instance/{$instance->id}", [
                'user_id' => null,
                'backup_result_id' => $backupResult->id,
                'status' => true,
                'message' => 'deleted',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['backup_result_id']);
    }

    public function test_successful_automatic_deletion_soft_deletes_without_notification(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create();
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inDeletionProcess(null)->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/delete/instance/{$instance->id}", [
                'user_id' => null,
                'backup_result_id' => $backupResult->id,
                'status' => true,
                'message' => 'auto-deleted',
            ])
            ->assertOk();

        $this->assertSoftDeleted('backup_results', ['id' => $backupResult->id]);
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_failed_manual_deletion_keeps_backup_and_emits_danger_notification(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create();
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->inDeletionProcess($user->id)->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/delete/instance/{$instance->id}", [
                'user_id' => $user->id,
                'backup_result_id' => $backupResult->id,
                'status' => false,
                'message' => 'could not delete file',
            ])
            ->assertOk();

        $this->assertDatabaseHas('backup_results', [
            'id' => $backupResult->id,
            'in_deletion_process' => 0,
            'moodle_job_id' => null,
            'deleted_at' => null,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }
}
