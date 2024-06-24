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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CheckPendingUpdateRequestsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $payload;

    /**
     * Create a new job instance.
     */
    public function __construct(public UpdateRequest $updateRequest)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->find($this->updateRequest->instance_id);

        try {
            $moduleApiService = new ModuleApiService();
            $moodleJobId = $this->updateRequest->moodle_job_id;

            if (is_null($moodleJobId)) {
                Log::error('Moodle job id is null for update request with id: :id.', ['id' => $this->updateRequest->id]);

                throw new \Exception('Moodle job id is null for update request with id: ' . $this->updateRequest->id . '.');
            }

            $this->payload = [
                'id' => $moodleJobId,
                'type' => $this->parseUpdateType($this->updateRequest->type),
            ];

            $response = $moduleApiService->triggerUpdateRequestCheck($instance->url, Crypt::decrypt($instance->api_key), $this->payload);

            if (!$response->ok()) {
                Log::error('Update request check of type :type and instance :instance failed with status code: :status.', ['type' => $this->updateRequest->type, 'instance' => $instance->name, 'status' => $response->status()]);

                throw new \Exception('Update request check failed with status code: ' . $response->status() . '.');
            }

            $body = $response->json();

            if (!array_key_exists('status', $body)) {
                Log::error('Invalid response body for update request check of type :type and instance :instance. Missing status', ['type' => $this->updateRequest->type, 'instance' => $instance->name]);

                throw new \Exception('Invalid response body for update request check of type: ' . $this->updateRequest->type . ' and instance: ' . $instance->name . '. Missing status.');
            }

            $this->updateRequestStatusUpdate($this->updateRequest, $body['status'], $body['error'] ?? "");

            $this->notify($this->updateRequest, $body['status'], $body['error'] ?? "");
        } catch (\Exception $exception) {
            Log::error(__METHOD__ . " line: " . __LINE__ . ' - ' . 'Failed to request for pending update-request status check for instance: ' . $instance->name . " Error message: " . $exception->getMessage());

            throw $exception;
        }
    }

    /**
     * Update Updaterequest status and error
     *
     * @param  Instance $instance
     * @param  User $userToNotify
     * @param  array $payload
     * @return void
     */
    private function updateRequestStatusUpdate(UpdateRequest $updateRequest, int $status, string $error = ""): void
    {
        $status = $this->parseStatus($status);

        $updateRequest->update([
            'status' => $status,
        ]);

        $updateRequest->items()->update([
            'status' => $status,
            'error' => $error,
        ]);
    }

    /**
     * Notify user about the status of the update request
     *
     * @param  UpdateRequest $updateRequest
     * @param  int $statusNumber
     * @param  string $error
     * @return void
     */
    private function notify(UpdateRequest $updateRequest, int $statusNumber, string $error = ""): void
    {
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->find($updateRequest->instance_id);
        
        $status = $this->parseStatus($statusNumber);
        $statusColor = $this->getStatusColor($status);
        
        $notificationTitle = $this->getNotificationTitle($status);
        $notificationBody = $this->getNotificationBody($instance, $status, $error);

        Notification::make()
            ->status($statusColor)
            ->title($notificationTitle)
            ->body($notificationBody)
            ->actions([
                Action::make('view')
                    ->color($statusColor)
                    ->button()
                    ->url(route('filament.app.pages.app-dashboard', ['tenant' => $instance]), shouldOpenInNewTab: true),
                Action::make('cancel')
                    ->color('secondary')
                    ->close(),
            ])
            ->icon('heroicon-o-arrow-up-circle')
            ->iconColor($statusColor)
            ->sendToDatabase($updateRequest->user);
    }

    /**
     * Get the count of successful updates
     *
     * @param  array $validatedData
     * @return int
     */
    private function getNotificationTitle(?bool $status): string
    {
        return match ($status) {
            true => __('Instance plugin updates successful!'),
            false => __('Plugin updates failed!'),
            default => __('Plugin updates in progress.'),
        };
    }

        
    /**
     * Get notification body
     *
     * @param  Instance $instance
     * @param  ?bool $status
     * @param  string $error
     * @return string
     */
    private function getNotificationBody(Instance $instance, ?bool $status, string $error): string
    {
        return match ($status) {
            true => __('Plugin updates for instance :instance have been successful.', ['instance' => $instance->name]),
            false => __('Plugin updates for instance :instance have failed with error-> :error.', ['instance' => $instance->name, 'error' => $error]),
            default => __('Plugin updates for instance :instance are still in progress.', ['instance' => $instance->name]),
        };
    }
        
    /**
     * Parse status from int to bool
     *
     * @param  int $status
     * @return bool
     */
    private function parseStatus(int $status): bool|null
    {
        switch ($status) {
            case 1:
                return true;
            case 2:
                return null;
            case 3:
                return false;
            default:
                return null;
        }
    }

    /**
     * Returns the color of the status
     *
     * @param  mixed $successfulUpdates
     * @param  mixed $allUpdates
     * @return string
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
     *
     * @param  string $type
     * @return string
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