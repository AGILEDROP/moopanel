<?php

namespace App\Jobs\Update;

use App\Models\Instance;
use App\Models\Update;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use App\Services\ModuleApiService;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CoreUpdateRequestJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private bool $canSubmitRequest;

    // Has to be max_tries(from config) + 1
    public $tries = 4;

    private UpdateRequest $updateRequest;

    /**
     * Create a new job instance.
     */
    public function __construct(private Instance $instance, private Update $update, private User $userToNotify, private array $payload)
    {
        // Skip request submission if there is already a pending request for the instance.
        $this->canSubmitRequest = ! $instance->hasPendingUpdateRequest('core');

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
            Log::info('Skipping core update request for instance: '.$this->instance->name.' as there is already a pending request.');

            Notification::make()
                ->warning()
                ->title(__('Core updates already in progress.'))
                ->body(__('Core updates for instance :instance are already in progress. We will notify you once the updates are completed.', ['instance' => $this->instance->name]))
                ->icon('fas-cube')
                ->iconColor('warning')
                ->sendToDatabase($this->userToNotify);

            return;
        }

        try {
            $moduleApiService = new ModuleApiService();

            $response = $moduleApiService->triggerCoreUpdate($this->instance, $this->payload);

            if (! $response->ok()) {

                // Retry job if service is temporarily unavailable
                $maxRetries = config('queue.jobs.core-update.max_tries') ?? 3;
                if ($response->status() == 503 && $this->attempts() <= $maxRetries) {
                    Log::info('Retrying core update request for instance: '.$this->instance->name.' as the service is temporarily unavailable.');

                    $retryAfter = config('queue.jobs.core-update.retry_after');
                    $this->release($retryAfter);

                    return;
                }

                Log::error('Core update for instance failed with status code and body: '.$response->status().' - '.$response->body());

                throw new \Exception('Core update failed with status code: '.$response->status().'.');
            }

            $response = $response->json();

            // Write moodle job id to be able to track the job
            if (array_key_exists('moodle_job_id', $response)) {
                if (is_null($this->updateRequest)) {
                    $this->updateRequest = UpdateRequest::where('instance_id', $this->instance->id)
                        ->where('status', UpdateRequest::STATUS_PENDING)
                        ->where('type', 'core')
                        ->firstOrFail();
                }

                $this->updateRequest->update([
                    'moodle_job_id' => $response['moodle_job_id'],
                ]);
            }

            if (isset($response['status']) && $response['status']) {
                Notification::make()
                    ->success()
                    ->title(__('Core update in progress'))
                    ->body(__('Core update for instance :instance is in progress. We will notify you once the updates are completed.', ['instance' => $this->instance->name]))
                    ->icon('fas-cube')
                    ->iconColor('success')
                    ->sendToDatabase($this->userToNotify);
            } else {
                $this->handleFailedUpdate($response);
            }
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
                    ->title(__('Core update failed!'))
                    ->body(__('Failed to request for core update on instance :instance. Please contact administrator for more information.', ['instance' => $this->instance->name]))
                    ->icon('fas-cube')
                    ->iconColor('danger')
                    ->sendToDatabase($this->userToNotify);
            }

            throw $exception;
        }

        Log::info('Core update request job started for instance: '.$this->instance->name.' for update with version: '.$this->update->version);
    }

    /**
     * Create update request for the instance.
     */
    private function createUpdateRequest(Instance $instance, User $userToNotify, array $payload): void
    {
        $name = UpdateRequest::generateName($instance->short_name, UpdateRequest::TYPE_CORE);

        $this->updateRequest = UpdateRequest::create([
            'name' => $name,
            'type' => UpdateRequest::TYPE_CORE,
            'instance_id' => $instance->id,
            'user_id' => $userToNotify->id,
            'status' => UpdateRequest::STATUS_PENDING,
            'payload' => json_encode($payload),
        ]);

        UpdateRequestItem::create([
            'update_request_id' => $this->updateRequest->id,
            'status' => UpdateRequest::STATUS_PENDING,
            'model_id' => $this->update->id,
            'version' => $this->update->version,
            'release' => $this->update->release,
            'download' => $this->update->download,
            'zip_path' => $this->update->url,
        ]);

        Log::info('Core update request entry created for instance: '.$instance->name.' for update with version: '.$this->update->version);
    }

    private function handleFailedUpdate(array $response): void
    {
        Notification::make()
            ->danger()
            ->title(__('Core update failed'))
            ->body(__(
                'Core update for instance :instance failed. :message',
                [
                    'instance' => $this->instance->name,
                    'message' => (isset($response['message']) ? $response['message'] : ''),
                ]
            ))
            ->actions([
                Action::make('view')
                    ->color('danger')
                    ->button()
                    ->url(route('filament.app.pages.app-dashboard', ['tenant' => $this->instance]), shouldOpenInNewTab: true),
                Action::make('cancel')
                    ->color('secondary')
                    ->close(),
            ])
            ->icon('fas-cube')
            ->iconColor('danger')
            ->sendToDatabase($this->userToNotify);

        // Mark update request as failed
        if (is_null($this->updateRequest)) {
            $this->updateRequest = UpdateRequest::where('instance_id', $this->instance->id)
                ->where('status', UpdateRequest::STATUS_PENDING)
                ->where('type', 'core')
                ->firstOrFail();
        }

        $this->updateRequest->update([
            'status' => UpdateRequest::STATUS_FAIL,
        ]);

        $this->updateRequest->items()
            ->update([
                'status' => UpdateRequest::STATUS_FAIL,
                'error' => $response['message'],
            ]);

        Log::info('Core update request failed for instance: '.$this->instance->name.' with response message: '.$response['message']);
    }
}
