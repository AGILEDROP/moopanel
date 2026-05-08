<?php

namespace Tests\Feature\Http;

use App\Models\BackupResult;
use App\Models\Course;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CourseBackupControllerTest extends TestCase
{
    public function test_index_returns_empty_successful_payload_when_no_backups_exist(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create(['url' => 'http://example.test']);
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 101]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->getJson("http://example.test/api/backups/course/{$course->moodle_course_id}")
            ->assertOk()
            ->assertJson([
                'status' => true,
                'backup_count' => 0,
                'auto_backup_count' => 0,
                'manual_backup_count' => 0,
            ]);
    }

    public function test_index_splits_manual_and_automatic_backups(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create(['url' => 'http://example.test']);
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 202]);

        BackupResult::factory()->forCourse($course)->successful()->automatic()->create([
            'url' => 'https://files/auto.mbz',
            'filesize' => 120,
        ]);
        BackupResult::factory()->forCourse($course)->successful()->manual(User::factory()->createQuietly(), 999)->create([
            'url' => 'https://files/manual.mbz',
            'filesize' => 240,
        ]);

        $response = $this->withHeader('X-API-KEY', 'secret-token')
            ->getJson("http://example.test/api/backups/course/{$course->moodle_course_id}");

        $response->assertOk()
            ->assertJson([
                'status' => true,
                'backup_count' => 2,
                'auto_backup_count' => 1,
                'manual_backup_count' => 1,
            ]);

        $this->assertCount(1, $response->json('auto'));
        $this->assertCount(1, $response->json('manual'));
    }

    public function test_index_rejects_invalid_api_key(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create(['url' => 'http://example.test']);
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 303]);

        $this->withHeader('X-API-KEY', 'wrong-token')
            ->getJson("http://example.test/api/backups/course/{$course->moodle_course_id}")
            ->assertStatus(403);
    }

    public function test_index_rejects_invalid_course_identifier(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create(['url' => 'http://example.test']);
        Course::factory()->for($instance)->create(['moodle_course_id' => 404]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->getJson('http://example.test/api/backups/course/0')
            ->assertStatus(422)
            ->assertJson(['message' => 'Invalid course id']);
    }

    public function test_store_updates_pending_backup_results_and_encrypts_password(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 505]);
        $pending = BackupResult::factory()->forCourse($course)->pending()->create([
            'moodle_job_id' => 111,
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'link' => 'https://files/new.mbz',
                'password' => 'backup-pass',
                'status' => true,
                'filesize' => 512,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $pending->refresh();

        $this->assertTrue($pending->status);
        $this->assertSame('https://files/new.mbz', $pending->url);
        $this->assertSame(512, $pending->filesize);
        $this->assertNull($pending->moodle_job_id);
        $this->assertSame('backup-pass', Crypt::decrypt($pending->password));
    }

    public function test_store_notifies_only_when_manual_batch_is_fully_resolved(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 606]);
        $timestamp = 123456;

        BackupResult::factory()->forCourse($course)->pending()->manual($user, $timestamp)->create(['moodle_job_id' => 222]);
        BackupResult::factory()->forCourse($course)->pending()->manual($user, $timestamp)->create(['moodle_job_id' => 223]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'link' => 'https://files/manual-batch.mbz',
                'password' => 'batch-pass',
                'status' => true,
            ])
            ->assertOk();

        $this->assertDatabaseCount('notifications', 2);
    }

    public function test_store_is_non_destructive_when_no_pending_rows_exist(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 707]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'link' => 'https://files/noop.mbz',
                'password' => 'noop-pass',
                'status' => false,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseCount('backup_results', 0);
    }

    public function test_store_marks_request_failed_when_payload_status_is_false(): void
    {
        // Status=false reports a failed backup. statusUpdate flips records to STATUS_FAILED
        // and the manual-batch notification still fires once all rows leave pending state.
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 808]);
        $timestamp = 222333;

        BackupResult::factory()->forCourse($course)->pending()->manual($user, $timestamp)->create(['moodle_job_id' => 800]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'link' => 'https://files/failed.mbz',
                'password' => 'fail-pass',
                'status' => false,
            ])
            ->assertOk()
            ->assertJson(['status' => true]);

        $this->assertDatabaseHas('backup_results', [
            'instance_id' => $instance->id,
            'moodle_course_id' => $course->moodle_course_id,
            'status' => false,
        ]);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_store_emits_warning_when_manual_batch_is_partially_successful(): void
    {
        $user = User::factory()->createQuietly();
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 909]);
        $timestamp = 444555;

        // One pending row that the request resolves.
        BackupResult::factory()->forCourse($course)->pending()->manual($user, $timestamp)->create(['moodle_job_id' => 901]);

        // A sibling row in the same batch that already settled as failed before the request.
        BackupResult::factory()->forCourse($course)->manual($user, $timestamp)->create([
            'status' => BackupResult::STATUS_FAILED,
            'moodle_job_id' => null,
        ]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'link' => 'https://files/partial.mbz',
                'password' => 'partial-pass',
                'status' => true,
            ])
            ->assertOk();

        // Manual batch fully settled (no rows remain pending) → notification fires.
        $this->assertDatabaseCount('notifications', 1);
    }
}
