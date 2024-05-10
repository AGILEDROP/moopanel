<?php

namespace App\Jobs\ModuleApi;

class SyncInstanceInfo extends BaseModuleApiJob
{
    public function handle(): void
    {
        $success = $this->moduleApiService->syncInstanceInfo($this->instance, true);
        if (! $success) {
            $this->fail('Instance information sync failed.');
        }
    }
}
