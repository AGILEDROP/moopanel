<?php

namespace App\Jobs\ModuleApi;

class SyncInstancePlugins extends BaseModuleApiJob
{
    public function handle(): void
    {
        $success = $this->moduleApiService->syncInstancePlugins($this->instance, true);
        if (! $success) {
            $this->fail('Plugin sync failed!');
        }
    }
}
