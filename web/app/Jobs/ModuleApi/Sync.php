<?php

namespace App\Jobs\ModuleApi;

use App\Models\Instance;
use App\Services\ModuleApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class Sync implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ModuleApiService $moduleApiService;

    public Instance $instance;

    public string $syncType;

    protected string $failMsg;

    public function __construct(Instance $instance, string $syncType, string $failMsg = 'Updates sync failed.')
    {
        $this->moduleApiService = new ModuleApiService();
        $this->instance = $instance;
        $this->syncType = $syncType;
        $this->failMsg = $failMsg;
    }

    public function handle(): void
    {
        $success = $this->moduleApiService->sync($this->instance, $this->syncType, true);
        if (! $success) {
            $this->fail($this->failMsg);
        }
    }
}
