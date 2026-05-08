<?php

namespace Database\Factories;

use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Category>
 */
class CategoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'instance_id' => Instance::factory(),
            'moodle_category_id' => fake()->unique()->numberBetween(100, 9999),
            'moodle_category_parent_id' => null,
            'name' => fake()->words(2, true),
            'depth' => 0,
            'path' => '/1',
        ];
    }
}
