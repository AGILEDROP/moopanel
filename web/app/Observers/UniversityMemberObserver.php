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
        $universityMember->users()->syncWithoutDetaching(User::pluck('id')->toArray());
    }

    public function deleted(UniversityMember $universityMember): void
    {
        $universityMember->users()->sync([]);
    }
}
