<?php

namespace Tests\Feature\Http;

use App\Models\Course;
use App\Models\Instance;
use Tests\TestCase;

class MiddlewareContractTest extends TestCase
{
    public function test_check_instance_token_rejects_invalid_api_key(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create();
        $course = Course::factory()->for($instance)->create();

        $this->withHeader('X-API-KEY', 'wrong-token')
            ->postJson("/api/backups/courses/{$instance->id}", [
                'courseid' => $course->moodle_course_id,
                'link' => 'https://files/test.mbz',
                'password' => 'pass',
                'status' => true,
            ])
            ->assertStatus(403);
    }

    public function test_check_instance_token_rejects_invalid_instance_id(): void
    {
        $this->withHeader('X-API-KEY', 'secret-token')
            ->postJson('/api/backups/courses/999999', [
                'courseid' => 1,
                'link' => 'https://files/test.mbz',
                'password' => 'pass',
                'status' => true,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['instance_id']);
    }

    public function test_check_referer_token_allows_valid_request_through(): void
    {
        $instance = Instance::factory()->withApiKey('secret-token')->create(['url' => 'http://example.test']);
        $course = Course::factory()->for($instance)->create(['moodle_course_id' => 999]);

        $this->withHeader('X-API-KEY', 'secret-token')
            ->getJson("http://example.test/api/backups/course/{$course->moodle_course_id}")
            ->assertOk();
    }

    public function test_check_referer_token_rejects_missing_instance_for_referer(): void
    {
        $this->withHeader('X-API-KEY', 'secret-token')
            ->getJson('http://unknown.test/api/backups/course/123')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['referer_url']);
    }
}
