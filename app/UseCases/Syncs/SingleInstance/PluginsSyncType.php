<?php

namespace App\UseCases\Syncs\SingleInstance;

use App\Models\Sync;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class PluginsSyncType extends BaseSyncType
{
    const TYPE = 'plugins';

    /**
     * @throws Exception
     */
    protected function syncData(): void
    {
        $request = $this->moduleApiService->getPlugins($this->instance->url, Crypt::decrypt($this->instance->api_key));
        if (! $request->ok()) {
            throw new Exception("Plugins sync failed due to unsuccessful plugins update (instance id: {$this->instance->id})!");
        }

        $this->syncHelper->instancePluginsCrudAction($request->json('plugins'), $this->instance);

        Sync::withoutGlobalScopes()->updateOrCreate([
            'instance_id' => $this->instance->id,
            'type' => static::TYPE,
        ], ['synced_at' => now()]);
    }

    protected function getSuccessNotification(): void
    {
        Notification::make()
            ->title(__('Plugins data is synced.'))
            ->success()
            ->send();
    }

    protected function getFailNotification(): void
    {
        Notification::make()
            ->title(__('Plugins sync failed!'))
            ->danger()
            ->send();
    }
}
