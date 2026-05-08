<?php

namespace Database\Factories;

use App\Models\Instance;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BackupStorage>
 */
class BackupStorageFactory extends Factory
{
    public function definition(): array
    {
        return [
            'instance_id' => Instance::factory(),
            'active' => true,
            'name' => 'Local Storage',
            'storage_key' => 'local',
            'url' => null,
            'key' => null,
            'secret' => null,
            'bucket_name' => null,
            'region' => null,
        ];
    }
}
