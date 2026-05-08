<?php

namespace Tests\Feature\Http;

use App\Models\BackupResult;
use App\Models\Course;
use App\Models\Instance;
use App\Models\Update;
use App\Models\User;
use Tests\TestCase;

class RequestValidationTest extends TestCase
{
    public function test_core_update_requires_existing_user_and_instance_bound_update(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $otherUpdate = Update::factory()->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/core/instance/{$instance->id}", [
                'user_id' => 999999,
                'moodle_job_id' => 10,
                'status' => true,
                'message' => 'done',
                'update_id' => $otherUpdate->id,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'update_id']);
    }

    public function test_plugin_update_requires_nested_payload_fields(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 11,
                'updates' => [[]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['updates.0.model_id', 'updates.0.status', 'updates.0.component']);
    }

    public function test_plugin_zip_update_requires_nested_payload_fields(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/updates/zip-plugins/instance/{$instance->id}", [
                'user_id' => $user->id,
                'moodle_job_id' => 12,
                'updates' => [[]],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['updates.0.link', 'updates.0.status', 'updates.0.component']);
    }

    public function test_course_backup_create_requires_course_to_belong_to_instance(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $otherCourse = Course::factory()->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $otherCourse->moodle_course_id,
                'link' => 'https://files/test.mbz',
                'password' => 'secret',
                'status' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['courseid']);
    }

    public function test_backup_restore_requires_existing_user_and_backup_result(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/restore/instance/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'status' => true,
                'message' => 'ok',
                'user_id' => 999999,
                'backup_result_id' => 999999,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['user_id', 'backup_result_id']);
    }

    public function test_backup_deletion_requires_in_deletion_process_backup_result(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create();
        $backupResult = BackupResult::factory()->forCourse($course)->successful()->create();

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/delete/instance/{$instance->id}", [
                'backup_result_id' => $backupResult->id,
                'status' => true,
                'message' => 'ok',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['backup_result_id']);
    }
}
