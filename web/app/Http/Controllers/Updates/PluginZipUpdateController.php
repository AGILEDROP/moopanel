<?php

namespace App\Http\Controllers\Updates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Update\PluginZipUpdateCreate;
use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\UpdateRequest;
use App\Models\User;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use Exception;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PluginZipUpdateController extends Controller
{
    public function store(PluginZipUpdateCreate $request, $instance_id): JsonResponse
    {
        $validatedData = $request->validated();
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instance_id)->first();
        $isSuccessfull = true;

        // Avoid duplicated notifications if request repeated for already successful update
        if ($instance->hasPendingUpdateRequest()) {
            $isSuccessfull = $isSuccessfull && $this->statusUpdate($instance, $validatedData);
            $this->notify($validatedData, $instance);

            // Sync instances available plugins
            if ($isSuccessfull) {
                Sync::dispatch($instance, PluginsSyncType::TYPE, 'Plugin sync failed!');
            }
        }

        if (!$isSuccessfull) {
            return response()->json([
                'message' => 'There was and error while updating the plugin statuses. Please try again or contact support.',
                'status' => false,
            ], 500);
        }

        return response()->json([
            'message' => 'Plugin ZIP update statuses received successfully.',
            'status' => true,
        ]);
    }

    /**
     * Notifies the user about the plugin updates
     * 
     * The notification will be sent to the user with the given user_id
     * Notification contains link to instance dashboard where user can see update request status in detail
     *
     * @param  mixed $validatedData
     * @param  Instance $instance
     * @return void
     */
    private function notify(array $validatedData, Instance $instance): void
    {
        $successfullUpdates = $this->getSuccessUpdatesCount($validatedData);
        $allUpdates = $this->getUpdatesCount($validatedData);
        $status = $this->getResponseStatus($successfullUpdates, $allUpdates);
        $message = $this->getResponseMessage($successfullUpdates, $allUpdates);

        Notification::make()
            ->status($status)
            ->title(__($message))
            ->body(__(':count plugins for instance :instance_name have been updated with ZIP files.', ['count' => $successfullUpdates . '/' . $allUpdates, 'instance_name' => $instance->name]))
            ->actions([
                Action::make('view')
                    ->color($status)
                    ->button()
                    ->url(route('filament.app.pages.app-dashboard', ['tenant' => $instance]), shouldOpenInNewTab: true),
                Action::make('cancel')
                    ->color('secondary')
                    ->close(),
            ])
            ->icon('heroicon-o-arrow-up-circle')
            ->iconColor($status)
            ->sendToDatabase(User::find($validatedData['user_id']));
    }

    /**
     * Updates the status of the update request and its items
     *
     * @param  Instance $instance
     * @param  array $data
     * @return void
     */
    private function statusUpdate(Instance $instance, array $data): bool
    {
        $status = true;

        try {
            $successfullUpdates = array_filter($data['updates'], function ($update) {
                return $update['status'];
            });
            $allUpdatesSuccessful = count($successfullUpdates) === count($data['updates']);

            // update parent UpdateRequest status
            $updateRequest = UpdateRequest::where('instance_id', $instance->id)
                ->where('status', UpdateRequest::STATUS_PENDING)
                ->first();

            $updateRequest->update([
                'status' => $allUpdatesSuccessful ? UpdateRequest::STATUS_SUCCESS : UpdateRequest::STATUS_FAIL,
                'moodle_job_id' => $data['moodle_job_id'],
            ]);

            $updateItems = $updateRequest->items;

            // update update-request-items statuses
            foreach ($data['updates'] as $update) {
                $updateItems->where('zip_path', $update['link'])
                    ->first()
                    ->update([
                        'status' => $update['status'],
                        'error' => $update['error'],
                        'component' => isset($update['component']) ? $update['component'] : " - ",
                        'version' => isset($update['version']) ? $update['version'] : " - ",
                    ]);
            }
        } catch (Exception $e) {
            Log::error(__FILE__ . ':' . __LINE__ . ' - ' . 'Failed to update ZIP update plugin request status for instance: ' . $instance->name . " Error message: " . $e->getMessage());

            $status = false;
        }

        return $status;
    }

    /**
     * Returns the response status based on the number of successful updates
     *
     * @param  mixed $successfulUpdates
     * @param  mixed $allUpdates
     * @return string
     */
    private function getResponseStatus(int $successfulUpdates, int $allUpdates): string
    {
        $status = 'success';

        if ($successfulUpdates === 0) {
            $status = 'danger';
        } elseif ($successfulUpdates < $allUpdates) {
            $status = 'warning';
        }

        return $status;
    }

    /**
     * Returns the response message based on the number of successful updates
     *
     * @param  mixed $successfulUpdates
     * @param  mixed $allUpdates
     * @return string
     */
    private function getResponseMessage(int $successfulUpdates, int $allUpdates): string
    {
        $message = __('Instance plugin ZIP update successfull');

        if ($successfulUpdates === 0) {
            $message = __('Instance plugin ZIP update failed');
        } elseif ($successfulUpdates < $allUpdates) {
            $message = __('Instance plugin ZIP update partially successfull');
        }

        return $message;
    }

    /**
     * Returns the number of successful updates
     *
     * @param  mixed $validatedData
     * @return int
     */
    private function getSuccessUpdatesCount(array $validatedData): int
    {
        return array_reduce($validatedData['updates'], function ($carry, $item) {
            return $carry + ($item['status'] ? 1 : 0);
        }, 0);
    }

    /**
     * Returns the number of updates
     *
     * @param  mixed $validatedData
     * @return int
     */
    private function getUpdatesCount(array $validatedData): int
    {
        return count($validatedData['updates']);
    }
}
