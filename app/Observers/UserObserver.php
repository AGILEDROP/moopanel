<?php

namespace App\Observers;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\UniversityMember;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        // Assign user all university members on creation.
        $universityMembers = UniversityMember::query()
            ->pluck('id')
            ->toArray();

        $user->universityMembers()->sync($universityMembers);

        // In first phase all users should have access to all instances (later we will add instances based on user role)!
        $instances = Instance::query()
            ->withoutGlobalScope(InstanceScope::class)
            ->pluck('id')
            ->toArray();

        $user->instances()->attach($instances);
    }

    public function deleted(User $user): void
    {
        $user->universityMembers()->detach();
        $user->instances()->detach();
    }
}
