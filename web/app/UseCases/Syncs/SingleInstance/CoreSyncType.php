<?php

namespace App\UseCases\Syncs\SingleInstance;

use App\Models\Sync;
use Exception;
use Filament\Notifications\Notification;

class CoreSyncType extends BaseSyncType
{
    const TYPE = 'core';

    /**
     * @throws Exception
     */
    protected function syncData(): void
    {
        $request = $this->moduleApiService->getCoreUpdates($this->instance);
        if (! $request->ok()) {
            throw new Exception("Core updates sync for instance {$this->instance->id} failed! Exit status {$request->status()}");
        }

        $this->instance->update([
            'version' => $request->json('current_version'),
        ]);

        if ($request->json('update_available') !== null) {
            $this->syncHelper->updatesCrudAction($request->json('update_available'), $this->instance->id);
        }

        if ($request->json('update_log') !== null) {
            $this->syncHelper->updateLogCrudAction($request->json('update_log'), $this->instance->id);
        }

        Sync::withoutGlobalScopes()->updateOrCreate([
            'instance_id' => $this->instance->id,
            'type' => static::TYPE,
        ], ['synced_at' => now()]);
    }

    protected function getSuccessNotification(): void
    {
        Notification::make()
            ->title(__('Core data is synced.'))
            ->success()
            ->send();
    }

    protected function getFailNotification(): void
    {
        Notification::make()
            ->title(__('Core sync failed!'))
            ->danger()
            ->send();
    }
}
