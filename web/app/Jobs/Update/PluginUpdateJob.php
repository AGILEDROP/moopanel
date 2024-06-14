<?php

namespace App\Jobs\Update;

use App\Models\Instance;
use App\Models\User;
use App\Services\ModuleApiService;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PluginUpdateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(public Instance $instance, private User $userToNotify, private array $payload)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            $moduleApiService = new ModuleApiService();

            $response = $moduleApiService->triggerPluginsUpdates($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (!$response->ok()) {
                Log::error('Plugin update for instance failed with status code and body: ' . $response->status() . ' - ' . $response->body());

                throw new \Exception('Plugin update failed with status code: ' . $response->status() . '.');
            }
           
            $pluginUpdatesCount = isset($this->payload['updates']) ? count($this->payload['updates']) : null;

            if ($pluginUpdatesCount === null) {
                throw new \Exception('No plugin updates found in payload for instance: ' . $this->instance->name);
            }

            Notification::make()
                ->success()
                ->title(__('Plugin updates in progress.'))
                ->body(__('Plugin updates(:count) for instance :instance are in progress. We will notify you once the updates are completed.', ['count' => $pluginUpdatesCount, 'instance' => $this->instance->name]))
                ->icon('heroicon-o-arrow-up-circle')
                ->iconColor('success')
                ->sendToDatabase($this->userToNotify);
        } catch (\Exception $exception) {
            //TODO: if service unavailable, retry after some time

            $errorMessage = sprintf(
                'Exception in %s on line %s in method %s: %s',
                $exception->getFile(),
                $exception->getLine(),
                __METHOD__,
                $exception->getMessage()
            );

            Log::error($errorMessage);

            Notification::make()
                ->danger()
                ->title(__('Plugin updates failed!'))
                ->body(__('Failed to request for plugin updates on instance :instance. Please contact administrator for more information.', ['instance' => $this->instance->name]))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->sendToDatabase($this->userToNotify);

            throw $exception;
        }
    }
}
