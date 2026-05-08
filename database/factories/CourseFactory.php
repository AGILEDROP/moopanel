<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Course>
 */
class CourseFactory extends Factory
{
    public function definition(): array
    {
        $instance = Instance::factory();

        return [
            'moodle_course_id' => fake()->unique()->numberBetween(1000, 99999),
            'instance_id' => $instance,
            'category_id' => Category::factory()->state([
                'instance_id' => $instance,
            ]),
            'name' => fake()->sentence(3),
            'is_scheduled' => false,
        ];
    }
}
