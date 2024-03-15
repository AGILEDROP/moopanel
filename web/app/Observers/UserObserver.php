<?php

namespace App\Observers;

use App\Models\UniversityMember;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        $user->universityMembers()->syncWithoutDetaching(UniversityMember::pluck('id')->toArray());
    }

    public function deleted(User $user): void
    {
        $user->universityMembers()->sync([]);
    }
}
