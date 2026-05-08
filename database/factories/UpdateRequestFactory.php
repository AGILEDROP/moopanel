<?php

namespace Database\Factories;

use App\Models\Instance;
use App\Models\UpdateRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UpdateRequest>
 */
class UpdateRequestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'update-request-'.fake()->unique()->numerify('###'),
            'type' => UpdateRequest::TYPE_CORE,
            'instance_id' => Instance::factory(),
            'user_id' => User::factory(),
            'status' => UpdateRequest::STATUS_PENDING,
            'payload' => json_encode(['items' => []]),
            'moodle_job_id' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['status' => UpdateRequest::STATUS_PENDING]);
    }

    public function successful(): static
    {
        return $this->state(fn () => ['status' => UpdateRequest::STATUS_SUCCESS]);
    }

    public function failed(): static
    {
        return $this->state(fn () => ['status' => UpdateRequest::STATUS_FAIL]);
    }
}
