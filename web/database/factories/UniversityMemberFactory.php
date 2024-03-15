<?php

namespace Database\Factories;

use Carbon\Carbon;
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
            'sis_base_url' => fake()->url(),
            'sis_current_year' => Carbon::create('Y')->toString().'-'.Carbon::create('Y')->addYear()->toString(),
            'sis_student_years' => rand(1, 5),
        ];
    }
}
