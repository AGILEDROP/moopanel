<?php

namespace Database\Seeders;

use App\Models\Instance;
use App\Models\User;
use Illuminate\Database\Seeder;

class InstanceUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $instances = Instance::all();
        foreach ($instances as $instance) {
            $instance->users()->attach(User::pluck('id')->toArray());
        }
    }
}
