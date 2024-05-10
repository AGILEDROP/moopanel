<?php

namespace App\Observers;

use App\Models\Instance;
use App\Models\UniversityMember;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        // Assign user all university members on creation.
        $universityMembers = (UniversityMember::count() > 0) ? UniversityMember::pluck('id')->toArray() : [];
        $user->universityMembers()->syncWithoutDetaching($universityMembers);
        // In first phase all users should have access to all instances (later we will add instances based on user role)!
        $user->instances()->attach(Instance::pluck('id')->toArray());
    }

    public function deleted(User $user): void
    {
        $user->universityMembers()->sync([]);
        $user->instances()->sync([]);
    }
}
