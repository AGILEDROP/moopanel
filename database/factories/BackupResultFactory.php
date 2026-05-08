<?php

namespace Database\Factories;

use App\Models\BackupStorage;
use App\Models\Course;
use App\Models\Instance;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BackupResult>
 */
class BackupResultFactory extends Factory
{
    public function definition(): array
    {
        $course = Course::factory();

        return [
            'instance_id' => Instance::factory(),
            'course_id' => $course,
            'moodle_course_id' => 12345,
            'manual_trigger_timestamp' => null,
            'user_id' => null,
            'url' => fake()->url(),
            'status' => null,
            'password' => null,
            'message' => null,
            'filesize' => 0,
            'backup_storage_id' => null,
            'in_deletion_process' => false,
            'in_restore_process' => false,
            'moodle_job_id' => null,
        ];
    }

    public function forCourse(Course $course): static
    {
        return $this->state(fn () => [
            'course_id' => $course->id,
            'instance_id' => $course->instance_id,
            'moodle_course_id' => $course->moodle_course_id,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => null]);
    }

    public function successful(): static
    {
        return $this->state(fn () => ['status' => true]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => false]);
    }

    public function manual(?User $user = null, ?int $timestamp = null): static
    {
        return $this->state(fn () => [
            'manual_trigger_timestamp' => $timestamp ?? now()->timestamp,
            'user_id' => $user?->id ?? User::factory(),
        ]);
    }

    public function automatic(): static
    {
        return $this->state(fn () => [
            'manual_trigger_timestamp' => null,
            'user_id' => null,
        ]);
    }

    public function inRestoreProcess(): static
    {
        return $this->state(fn () => [
            'in_restore_process' => true,
            'moodle_job_id' => 1234,
        ]);
    }

    public function inDeletionProcess(?int $userId = null): static
    {
        return $this->state(fn () => [
            'in_deletion_process' => true,
            'moodle_job_id' => 5678,
            'user_id' => $userId,
        ]);
    }
}
