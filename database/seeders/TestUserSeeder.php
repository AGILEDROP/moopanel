<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'test@example.com'],
            [
                'username' => 'test',
                'name' => 'Test User',
                'email_verified_at' => now(),
                'password' => Hash::make('password'),
                'remember_token' => Str::random(10),
                'azure_id' => (string) Str::uuid(),
                'employee_id' => null,
                'app_role_id' => collect([
                    ['value' => json_encode(['value' => Role::MasterAdmin->value])],
                ]),
            ]
        );
    }
}
