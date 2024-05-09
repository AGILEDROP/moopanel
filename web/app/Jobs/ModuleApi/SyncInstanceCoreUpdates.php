<?php

namespace App\Jobs\ModuleApi;

class SyncInstanceCoreUpdates extends BaseModuleApiJob
{
    public function handle(): void
    {
        $this->moduleApiService->syncInstanceCoreUpdates($this->instance, true);
    }
}
