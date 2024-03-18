<?php

namespace App\Observers;

use App\Models\UniversityMember;
use App\Models\User;

class UserObserver
{
    public function created(User $user): void
    {
        $universityMembers = (UniversityMember::count() > 0) ? UniversityMember::pluck('id')->toArray() : [];
        $user->universityMembers()->syncWithoutDetaching($universityMembers);
    }

    public function deleted(User $user): void
    {
        $user->universityMembers()->sync([]);
    }
}
