<?php

namespace App\Jobs\Update;

use App\Models\Instance;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use App\Services\ModuleApiService;
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

    // Has to be max_tries(from config) + 1
    public $tries = 4;

    private bool $canSubmitRequest;

    private UpdateRequest $updateRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(public Instance $instance, private User $userToNotify, private array $payload)
    {
        // Skip request submission if there is already a pending request for the instance.
        $this->canSubmitRequest = ! $instance->hasPendingUpdateRequest();

        if ($this->canSubmitRequest) {
            $this->createUpdateRequest($instance, $userToNotify, $payload);
        }
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Abort if there is already a pending request for the instance.
        if (! $this->canSubmitRequest) {
            Log::info('Skipping plugin update request for instance: '.$this->instance->name.' as there is already a pending request.');

            Notification::make()
                ->warning()
                ->title(__('Plugin updates already in progress.'))
                ->body(__('Plugin updates for instance :instance are already in progress. We will notify you once the updates are completed.', ['instance' => $this->instance->name]))
                ->icon('heroicon-o-arrow-up-circle')
                ->iconColor('warning')
                ->sendToDatabase($this->userToNotify);

            return;
        }

        try {
            $moduleApiService = new ModuleApiService();

            $response = $moduleApiService->triggerPluginsUpdates($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (! $response->ok()) {

                // Retry job if service is temporarily unavailable
                $maxRetries = config('queue.jobs.plugin-update.max_tries') ?? 3;
                if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                    Log::info('Retrying plugin update request for instance: '.$this->instance->name.' as the service is temporarily unavailable.');

                    $retryAfter = config('queue.jobs.plugin-update.retry_after');
                    $this->release($retryAfter);

                    return;
                }

                Log::error('Plugin update for instance failed with status code and body: '.$response->status().' - '.$response->body());

                throw new \Exception('Plugin update failed with status code: '.$response->status().'.');
            }

            $pluginUpdatesCount = isset($this->payload['updates']) ? count($this->payload['updates']) : null;

            if ($pluginUpdatesCount === null) {
                throw new \Exception('No plugin updates found in payload for instance: '.$this->instance->name);
            }

            if ($response->json() && array_key_exists('moodle_job_id', $response->json())) {

                $this->updateRequest->update([
                    'moodle_job_id' => $response->json('moodle_job_id'),
                ]);
            }

            Notification::make()
                ->success()
                ->title(__('Plugin updates in progress.'))
                ->body(__('Plugin updates(:count) for instance :instance are in progress. We will notify you once the updates are completed.', ['count' => $pluginUpdatesCount, 'instance' => $this->instance->name]))
                ->icon('heroicon-o-arrow-up-circle')
                ->iconColor('success')
                ->sendToDatabase($this->userToNotify);
        } catch (\Exception $exception) {

            $errorMessage = sprintf(
                'Exception in %s on line %s in method %s: %s',
                $exception->getFile(),
                $exception->getLine(),
                __METHOD__,
                $exception->getMessage()
            );

            Log::error($errorMessage);

            if ($this->attempts() >= ($this->tries)) {
                Notification::make()
                    ->danger()
                    ->title(__('Plugin updates failed!'))
                    ->body(__('Failed to request for plugin updates on instance :instance. Please contact administrator for more information.', ['instance' => $this->instance->name]))
                    ->icon('heroicon-o-x-circle')
                    ->iconColor('danger')
                    ->sendToDatabase($this->userToNotify);
            }

            throw $exception;
        }
    }

    /**
     * Create update request for the instance.
     */
    private function createUpdateRequest(Instance $instance, User $userToNotify, array $payload): void
    {
        $name = UpdateRequest::generateName($instance->short_name, UpdateRequest::TYPE_PLUGIN);

        $this->updateRequest = UpdateRequest::create([
            'name' => $name,
            'type' => UpdateRequest::TYPE_PLUGIN,
            'instance_id' => $instance->id,
            'user_id' => $userToNotify->id,
            'status' => UpdateRequest::STATUS_PENDING,
            'payload' => json_encode($payload),
        ]);

        if (! empty($payload['updates'])) {
            foreach ($payload['updates'] as $update) {
                UpdateRequestItem::create([
                    'update_request_id' => $this->updateRequest->id,
                    'status' => UpdateRequest::STATUS_PENDING,
                    'model_id' => $update['model_id'],
                    'component' => $update['component'],
                    'version' => $update['version'],
                    'release' => $update['release'],
                    'download' => $update['download'],
                ]);
            }
        }

        Log::info('Plugin update request created for instance: '.$instance->name);
    }
}
