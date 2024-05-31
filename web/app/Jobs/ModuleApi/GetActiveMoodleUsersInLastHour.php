<?php

namespace App\Jobs\ModuleApi;

use App\Models\ActiveMoodleUsersLog;
use App\Models\Instance;
use App\Services\ModuleApiService;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class GetActiveMoodleUsersInLastHour implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ModuleApiService $moduleApiService;

    public Instance $instance;

    public function __construct(Instance $instance)
    {
        $this->moduleApiService = new ModuleApiService();
        $this->instance = $instance;
    }

    public function handle(): void
    {
        $start = Carbon::now()->subHour()->startOfHour();
        $end = Carbon::now()->subHour()->endOfHour();
        $usersCount = $this->moduleApiService->getActiveMoodleUsersCount($this->instance, $start->unix(), $end->unix());

        ActiveMoodleUsersLog::withoutGlobalScopes()
            ->updateOrCreate([
                'instance_id' => $this->instance->id,
                'start_date' => $start,
                'end_date' => $end,
            ], [
                'active_num' => $usersCount,
            ]);
    }
}
