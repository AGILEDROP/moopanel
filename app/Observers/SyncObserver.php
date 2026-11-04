<?php

namespace App\Observers;

use App\Models\Sync;

class SyncObserver
{
    public function creating(Sync $sync): void
    {
        $sync->synced_at = $sync->freshTimestamp();
    }

    public function updating(Sync $sync): void
    {
        $sync->synced_at = $sync->freshTimestamp();
    }
}
