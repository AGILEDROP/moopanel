<?php

namespace Database\Factories;

use App\Enums\UpdateMaturity;
use App\Enums\UpdateType;
use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Update>
 */
class UpdateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'instance_id' => Instance::factory(),
            'plugin_id' => null,
            'version' => '2024010100',
            'release' => '4.3.1',
            'maturity' => UpdateMaturity::STABLE,
            'url' => fake()->url(),
            'type' => UpdateType::CORE_MINOR,
            'download' => fake()->url(),
            'downloadmd5' => md5('download'),
        ];
    }
}
