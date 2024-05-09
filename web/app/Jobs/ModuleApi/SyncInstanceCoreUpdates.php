<?php

namespace App\Jobs\ModuleApi;

class SyncInstanceCoreUpdates extends BaseModuleApiJob
{
    public function handle(): void
    {
        $success = $this->moduleApiService->syncInstanceCoreUpdates($this->instance, true);
        if (! $success) {
            $this->fail('Core updates sync failed.');
        }
    }
}
