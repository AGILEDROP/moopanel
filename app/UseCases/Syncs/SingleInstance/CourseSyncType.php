<?php

namespace App\UseCases\Syncs\SingleInstance;

use App\Models\Sync;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;

class CourseSyncType extends BaseSyncType
{
    const TYPE = 'courses';

    /**
     * @throws Exception
     */
    protected function syncData(): void
    {
        $request = $this->moduleApiService->getCourses($this->instance->url, Crypt::decrypt($this->instance->api_key));
        if (! $request->ok()) {
            throw new Exception("Course sync failed for (instance id: {$this->instance->id}). There was an error on Moodle side.");
        }

        $this->syncHelper->coursesCrudAction($request->json(), $this->instance);

        Sync::withoutGlobalScopes()->updateOrCreate([
            'instance_id' => $this->instance->id,
            'type' => static::TYPE,
        ], ['synced_at' => now()]);
    }

    protected function getSuccessNotification(): void
    {
        Notification::make()
            ->title(__('Courses are synced.'))
            ->success()
            ->send();
    }

    protected function getFailNotification(): void
    {
        Notification::make()
            ->title(__('Courses sync failed!'))
            ->danger()
            ->send();
    }
}
