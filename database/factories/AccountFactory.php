<?php

namespace Database\Factories;

use App\Enums\AccountTypes;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Account>
 */
class AccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'azure_id' => fake()->uuid(),
            'name' => fake()->name(),
            'username' => fake()->unique()->safeEmail(),
            'email' => fake()->unique()->safeEmail(),
            'type' => AccountTypes::Undefined->value,
        ];
    }
}
