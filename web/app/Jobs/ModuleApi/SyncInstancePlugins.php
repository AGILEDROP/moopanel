<?php

namespace App\Jobs\ModuleApi;

class SyncInstancePlugins extends BaseModuleApiJob
{
    public function handle(): void
    {
        $this->moduleApiService->syncInstancePlugins($this->instance, true);
    }
}
