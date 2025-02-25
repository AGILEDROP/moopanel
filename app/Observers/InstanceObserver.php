<?php

namespace App\Observers;

use App\Models\Instance;
use App\Models\User;

class InstanceObserver
{
    public function created(Instance $instance): void
    {
        // In first phase all users should have access to all instances (later we will add instances based on user role)!
        $instance->users()->attach(User::pluck('id')->toArray());
    }

    public function deleted(Instance $instance): void
    {
        $instance->users()->sync([]);
    }
}
