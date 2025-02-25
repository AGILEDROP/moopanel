<?php

namespace App\UseCases\Syncs\SingleInstance;

use App\Enums\Status;
use App\Models\Sync;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class InfoSyncType extends BaseSyncType
{
    const TYPE = 'info';

    /**
     * @throws Exception
     */
    protected function syncData(): void
    {
        $request = $this->moduleApiService->getInstanceData($this->instance->url, Crypt::decrypt($this->instance->api_key));
        if (! $request->ok()) {
            Log::error("Instance information sync failed (instance id: {$this->instance->id})!");
        }

        $results = $request->collect();

        $this->instance->update([
            'version' => $results['moodle_version'] ?? null,
            'theme' => $results['theme'] ?? null,
            'status' => Status::Connected,
        ]);

        Sync::withoutGlobalScopes()->updateOrCreate([
            'instance_id' => $this->instance->id,
            'type' => static::TYPE,
        ], ['synced_at' => now()]);
    }

    protected function getSuccessNotification(): void
    {
        Notification::make()
            ->title(__('Instance information synced.'))
            ->success()
            ->send();
    }

    protected function getFailNotification(): void
    {
        Notification::make()
            ->title(__('Instance information sync failed!'))
            ->danger()
            ->send();
    }
}
