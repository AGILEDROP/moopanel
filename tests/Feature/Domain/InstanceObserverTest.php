<?php

namespace Tests\Feature\Domain;

use App\Models\Instance;
use App\Models\User;
use Tests\TestCase;

class InstanceObserverTest extends TestCase
{
    public function test_creating_instance_attaches_all_existing_users(): void
    {
        $users = User::factory()->count(3)->createQuietly();

        $instance = Instance::factory()->create();

        $this->assertEqualsCanonicalizing(
            $users->pluck('id')->all(),
            $instance->users()->pluck('users.id')->all(),
        );
    }

    public function test_deleting_instance_detaches_users(): void
    {
        $instance = Instance::factory()->create();

        $instance->delete();

        $this->assertDatabaseCount('instance_user', 0);
    }
}
