<?php

namespace Database\Factories;

use App\Models\Update;
use App\Models\UpdateRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UpdateRequestItem>
 */
class UpdateRequestItemFactory extends Factory
{
    public function definition(): array
    {
        return [
            'update_request_id' => UpdateRequest::factory(),
            'status' => null,
            'model_id' => Update::factory(),
            'component' => 'mod_forum',
            'version' => '1.0.0',
            'release' => '1.0.0',
            'download' => fake()->url(),
            'zip_path' => null,
            'zip_name' => null,
            'error' => null,
        ];
    }
}
