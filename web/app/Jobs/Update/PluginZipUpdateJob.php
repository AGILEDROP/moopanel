<?php

namespace App\Jobs\Update;

use App\Models\User;
use App\Models\Instance;
use App\Models\UpdateRequest;
use Illuminate\Bus\Queueable;
use App\Models\UpdateRequestItem;
use App\Services\ModuleApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Queue\SerializesModels;
use Filament\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Http;

class PluginZipUpdateJob implements ShouldQueue
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
        $this->canSubmitRequest = !$instance->hasPendingUpdateRequest();

        if ($this->canSubmitRequest) {
            $this->createUpdateRequest($instance, $userToNotify, $this->payload);
        }

        unset($this->payload['temp_updates_data']);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Abort if there is already a pending request for the instance.
        if (!$this->canSubmitRequest) {
            Log::info('Skipping ZIP plugin update request for instance: ' . $this->instance->name . ' as there is already a pending request.');

            Notification::make()
                ->warning()
                ->title(__('Plugin updates already in progress.'))
                ->body(__('Plugin updates for instance :instance are already in progress. We will notify you once the updates are completed.', ['instance' => $this->instance->name]))
                ->icon('heroicon-o-arrow-up-circle')
                ->iconColor('warning')
                ->sendToDatabase($this->userToNotify);

            return;
        }

        $moduleApi = new ModuleApiService();

        try {
            $response = $moduleApi->triggerPluginZipFileUpdates($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (!$response->ok()) {

                // Retry job if service is temporarily unavailable
                $maxRetries = config('queue.jobs.plugin-zip-update.max_tries') ?? 3;
                if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                    Log::info('Retrying ZIP plugin update request for instance: ' . $this->instance->name . ' as the service is temporarily unavailable.');

                    $retryAfter = config('queue.jobs.plugin-zip-update.retry_after');
                    $this->release($retryAfter);

                    return;
                }

                Log::error('Plugin ZIP updates for instance failed with status code and body: ' . $response->status() . ' - ' . $response->body());

                throw new \Exception('Plugin update failed with status code: ' . $response->status() . '.');
            }

            $pluginUpdatesCount = isset($this->payload['updates']) ? count($this->payload['updates']) : null;

            if ($pluginUpdatesCount === null) {
                throw new \Exception('No ZIP plugin updates found in payload for instance: ' . $this->instance->name);
            }

            if ($response->json() && array_key_exists('moodle_job_id', $response->json())) {

                $this->updateRequest->update([
                    'moodle_job_id' => $response->json('moodle_job_id')
                ]);
            }

            Notification::make()
                ->success()
                ->title(__('Plugin ZIP updates in progress.'))
                ->body(__('Plugin ZIP updates(:count) for instance :instance are in progress. We will notify you once the updates are completed.', ['count' => $pluginUpdatesCount, 'instance' => $this->instance->name]))
                ->icon('heroicon-o-arrow-up-circle')
                ->iconColor('success')
                ->sendToDatabase($this->userToNotify);
        } catch (\Exception $exception) {
            $errorMessage = sprintf(
                'Failed to request for ZIP plugin update on instance %s . Exception in %s on line %s in method %s: %s',
                $this->instance->name,
                $exception->getFile(),
                $exception->getLine(),
                __METHOD__,
                $exception->getMessage()
            );

            Log::error($errorMessage);

            Notification::make()
                ->danger()
                ->title(__('Plugin ZIP updates failed!'))
                ->body(__('Failed to request for plugin ZIP updates on instance :instance. Please contact administrator for more information.', ['instance' => $this->instance->name]))
                ->icon('heroicon-o-x-circle')
                ->iconColor('danger')
                ->sendToDatabase($this->userToNotify);

            throw $exception;
        }
    }

    /**
     * Create update request for the instance.
     *
     * @param  Instance $instance
     * @param  User $userToNotify
     * @param  array $payload
     * @return void
     */
    private function createUpdateRequest(Instance $instance, User $userToNotify, array $payload): void
    {
        $name = UpdateRequest::generateName($instance->short_name, UpdateRequest::TYPE_PLUGIN_ZIP);

        $this->updateRequest = UpdateRequest::create([
            'name' => $name,
            'type' => UpdateRequest::TYPE_PLUGIN_ZIP,
            'instance_id' => $instance->id,
            'user_id' => $userToNotify->id,
            'status' => UpdateRequest::STATUS_PENDING,
            'payload' => json_encode($payload),
        ]);


        if (!empty($payload['temp_updates_data'])) {
            foreach ($payload['temp_updates_data'] as $update) {
                UpdateRequestItem::create([
                    'update_request_id' => $this->updateRequest->id,
                    'status' => UpdateRequest::STATUS_PENDING,
                    'zip_name' => $update['zip_name'],
                    'zip_path' => $update['zip_path']
                ]);
            }
        }

        Log::info('Plugin ZIP update request and request items created for instance: ' . $instance->name);
    }
}
