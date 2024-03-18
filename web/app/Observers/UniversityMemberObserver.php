<?php

namespace App\Observers;

use App\Models\UniversityMember;
use App\Models\User;

class UniversityMemberObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(UniversityMember $universityMember): void
    {
        $users = (User::count() > 0) ? User::pluck('id')->toArray() : [];
        $universityMember->users()->syncWithoutDetaching($users);
    }

    public function deleted(UniversityMember $universityMember): void
    {
        $universityMember->users()->sync([]);
    }
}
