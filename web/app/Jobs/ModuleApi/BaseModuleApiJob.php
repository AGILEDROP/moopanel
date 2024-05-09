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

class BaseModuleApiJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Instance $instance;

    public ModuleApiService $moduleApiService;

    public function __construct(Instance $instance)
    {
        $this->instance = $instance;
        $this->moduleApiService = new ModuleApiService();
    }
}
