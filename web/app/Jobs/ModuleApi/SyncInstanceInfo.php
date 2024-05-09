<?php

namespace App\Jobs\ModuleApi;

class SyncInstanceInfo extends BaseModuleApiJob
{
    public function handle(): void
    {
        $this->moduleApiService->syncInstanceInfo($this->instance, true);
    }
}
