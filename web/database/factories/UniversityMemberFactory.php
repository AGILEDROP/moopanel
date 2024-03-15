<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UniversityMember>
 */
class UniversityMemberFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => fake()->numberBetween(3000000, 3000999),
            'acronym' => toUpper(Str::random(rand(2, 5))),
            'name' => fake()->company(),
            'years_of_enrollment' => 3,
        ];
    }
}
