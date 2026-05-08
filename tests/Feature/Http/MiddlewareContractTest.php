<?php

namespace Tests\Feature\Http;

use App\Http\Middleware\CheckInstanceToken;
use App\Http\Middleware\CheckRefererToken;
use App\Models\Course;
use App\Models\Instance;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Validation\ValidationException;
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

    public function test_check_referer_token_rejects_invalid_api_key_with_403(): void
    {
        Instance::factory()->withApiKey('secret-token')->create(['url' => 'http://example.test']);

        $this->withHeader('X-API-KEY', 'wrong-token')
            ->getJson('http://example.test/api/backups/course/1')
            ->assertStatus(403)
            ->assertJson(['message' => 'You are not authorized']);
    }

    public function test_check_instance_token_throws_validation_exception_when_route_binding_is_missing(): void
    {
        // Routes always bind {instance_id}, so the null branch is exercised by calling the
        // middleware directly with a request whose route() returns null.
        $request = new class extends Request
        {
            public function route($param = null, $default = null)
            {
                return null;
            }
        };

        $this->expectException(ValidationException::class);

        (new CheckInstanceToken())->handle($request, fn ($r) => $r);
    }

    public function test_check_referer_token_throws_when_referer_is_not_a_valid_url(): void
    {
        // The middleware checks filter_var($refererUrl, FILTER_VALIDATE_URL) — bypass schemeAndHttpHost
        // by stubbing it via a custom request subclass.
        $request = new class('GET', '/api/test') extends Request
        {
            public function __construct(string $method, string $uri)
            {
                parent::__construct();
                $this->setMethod($method);
            }

            public function schemeAndHttpHost(): string
            {
                return 'not-a-url';
            }
        };

        $this->expectException(ValidationException::class);

        (new CheckRefererToken())->handle($request, fn ($r) => $r);
    }
}
