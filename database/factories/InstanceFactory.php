<?php

namespace Database\Factories;

use App\Enums\Status;
use App\Models\Cluster;
use App\Models\UniversityMember;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Instance>
 */
class InstanceFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->company().' Moodle';

        return [
            'cluster_id' => Cluster::factory(),
            'university_member_id' => UniversityMember::factory(),
            'name' => $name,
            'short_name' => fake()->unique()->bothify('inst-####'),
            'url' => fake()->unique()->url(),
            'img_path' => null,
            'theme' => 'boost',
            'version' => '4.3.0',
            'api_key' => Crypt::encrypt('instance-api-key'),
            'key_expiration_date' => now()->addMonth()->toDateString(),
            'status' => Status::Connected,
            'configuration_path' => '/var/www/moodle/config.php',
            'azure_app_id' => fake()->uuid(),
            'app_info' => null,
        ];
    }

    public function withAppInfo(?string $appId = null, ?string $roleId = null): static
    {
        return $this->state(fn () => [
            'app_info' => json_encode([
                'id' => $appId ?? fake()->uuid(),
                'appRoles' => [
                    [
                        'displayName' => 'User',
                        'id' => $roleId ?? fake()->uuid(),
                    ],
                ],
            ]),
        ]);
    }

    public function withApiKey(string $apiKey): static
    {
        return $this->state(fn () => [
            'api_key' => Crypt::encrypt($apiKey),
        ]);
    }
}
