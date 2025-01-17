<?php

namespace App\Jobs\Update;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\UpdateRequest;
use App\Models\User;
use App\Services\ModuleApiService;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class CheckPendingUpdateRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;

    public Instance $instance;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * Create a new job instance.
     */
    public function __construct(public UpdateRequest $updateRequest)
    {
        $this->instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->updateRequest->instance_id);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {

        try {
            $moduleApiService = new ModuleApiService();
            $moodleJobId = $this->updateRequest->moodle_job_id;

            if (is_null($moodleJobId)) {
                Log::error('Moodle job id is null for update request with id: :id.', ['id' => $this->updateRequest->id]);

                throw new \Exception('Moodle job id is null for update request with id: '.$this->updateRequest->id.'.');
            }

            $this->payload = [
                'id' => $moodleJobId,
                'type' => $this->parseUpdateType($this->updateRequest->type),
            ];

            $response = $moduleApiService->triggerTaskCheck($this->instance->url, Crypt::decrypt($this->instance->api_key), $this->payload);

            if (! $response->ok()) {
                Log::error(__('Update request check of type :type and instance :instance failed with status code: :status.', ['type' => $this->updateRequest->type, 'instance' => $this->instance->name, 'status' => $response->status()]));

                throw new \Exception('Update request check failed with status code: '.$response->status().'.');
            }

            $body = $response->json();

            if (! array_key_exists('status', $body)) {
                Log::error(__('Invalid response body for update request check of type :type and instance :instance. Missing status', ['type' => $this->updateRequest->type, 'instance' => $this->instance->name]));

                throw new \Exception('Invalid response body for update request check of type: '.$this->updateRequest->type.' and instance: '.$this->instance->name.'. Received body: '.json_encode($body));
            }

            $this->updateRequestStatusUpdate($body['status'], $body['error'] ?? '');

            if ($body['status'] !== UpdateRequest::CHECK_STATUS_PENDING) {
                $this->notify($body['status'], $body['error'] ?? '');
            }

            $this->logStatus($body);
        } catch (\Exception $exception) {
            Log::error(__METHOD__.' line: '.__LINE__.' - '.'Failed to request for pending update-request status check for instance: '.$this->instance->name.' Error message: '.$exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Update Updaterequest status and error
     */
    private function updateRequestStatusUpdate(int $status, string $error = ''): void
    {
        $status = $this->updateRequest->parseStatus($status);

        $this->updateRequest->update([
            'status' => $status,
        ]);

        $this->updateRequest->items()->update([
            'status' => $status,
            'error' => $error,
        ]);
    }

    /**
     * Notify user about the status of the update request
     */
    private function notify(int $statusNumber, string $error = ''): void
    {
        $status = $this->updateRequest->parseStatus($statusNumber);
        $statusColor = $this->getStatusColor($status);

        $notificationTitle = $this->getNotificationTitle($status);
        $notificationBody = $this->getNotificationBody($status, $error);

        Notification::make()
            ->status($statusColor)
            ->title($notificationTitle)
            ->body($notificationBody)
            ->actions([
                Action::make('view')
                    ->color($statusColor)
                    ->button()
                    ->url(route('filament.app.pages.app-dashboard', ['tenant' => $this->instance]), shouldOpenInNewTab: true),
                Action::make('cancel')
                    ->color('secondary')
                    ->close(),
            ])
            ->icon('heroicon-o-arrow-up-circle')
            ->iconColor($statusColor)
            ->sendToDatabase($this->updateRequest->user);
    }

    /**
     * Log the status of the update request
     */
    private function logStatus(array $data): void
    {
        $status = $this->updateRequest->parseStatus($data['status']);

        $notificationTitle = $this->getNotificationTitle($status);
        $notificationBody = $this->getNotificationBody($status, $data['error'] ?? '');

        Log::info($notificationTitle.'- Via update request checker job - '.$notificationBody);
    }

    /**
     * Get the count of successful updates
     *
     * @param  array  $validatedData
     * @return int
     */
    private function getNotificationTitle(?bool $status): string
    {
        switch ($this->updateRequest->type) {
            case UpdateRequest::TYPE_CORE:
                return match ($status) {
                    true => __('Core update successful!'),
                    false => __('Core update failed!'),
                    default => __('Core update in progress.'),
                };
            case UpdateRequest::TYPE_PLUGIN:
                return match ($status) {
                    true => __('Instance plugin update successful!'),
                    false => __('Plugin update failed!'),
                    default => __('Plugin update in progress.'),
                };
            case UpdateRequest::TYPE_PLUGIN_ZIP:
                return match ($status) {
                    true => __('Instance plugin ZIP update successful!'),
                    false => __('Plugin ZIP update failed!'),
                    default => __('Plugin ZIP update in progress.'),
                };
            default:
                return __('Update in progress.');
        }
    }

    /**
     * Get notification body
     *
     * @param  ?bool  $status
     */
    private function getNotificationBody(?bool $status, string $error): string
    {
        switch ($this->updateRequest->type) {
            case UpdateRequest::TYPE_CORE:
                return match ($status) {
                    true => __('Core update for :instance was successful.', ['instance' => $this->instance->name]),
                    false => __('Core update for :instance failed with error: :error', ['instance' => $this->instance->name, 'error' => $error]),
                    default => __('Core update for :instance is in progress.', ['instance' => $this->instance->name]),
                };
            case UpdateRequest::TYPE_PLUGIN:
                return match ($status) {
                    true => __('Plugin update for :instance was successful.', ['instance' => $this->instance->name]),
                    false => __('Plugin update for :instance failed with error: :error', ['instance' => $this->instance->name, 'error' => $error]),
                    default => __('Plugin update for :instance is in progress.', ['instance' => $this->instance->name]),
                };
            case UpdateRequest::TYPE_PLUGIN_ZIP:
                return match ($status) {
                    true => __('Plugin ZIP update for :instance was successful.', ['instance' => $this->instance->name]),
                    false => __('Plugin ZIP update for :instance failed with error: :error', ['instance' => $this->instance->name, 'error' => $error]),
                    default => __('Plugin ZIP update for :instance is in progress.', ['instance' => $this->instance->name]),
                };
            default:
                return __('Update for :instance is in progress.', ['instance' => $this->instance->name]);
        }
    }

    /**
     * Returns the color of the status
     *
     * @param  mixed  $successfulUpdates
     * @param  mixed  $allUpdates
     */
    private function getStatusColor(?bool $status): string
    {
        return match ($status) {
            true => 'success',
            false => 'danger',
            default => 'warning',
        };
    }

    /**
     * Parse update type
     */
    private function parseUpdateType(string $type): string
    {
        return match ($type) {
            UpdateRequest::TYPE_CORE => 'core_update',
            UpdateRequest::TYPE_PLUGIN => 'plugins_update',
            UpdateRequest::TYPE_PLUGIN_ZIP => 'plugins_install_zip',
            default => 'no-update-type',
        };
    }
}
