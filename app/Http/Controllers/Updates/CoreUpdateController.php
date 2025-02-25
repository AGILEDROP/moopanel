<?php

namespace App\Http\Controllers\Updates;

use App\Http\Controllers\Controller;
use App\Http\Requests\Update\CoreUpdateCreate;
use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\Update;
use App\Models\UpdateRequest;
use App\Models\UpdateRequestItem;
use App\Models\User;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use Exception;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CoreUpdateController extends Controller
{
    public function store(CoreUpdateCreate $request, $instance_id): JsonResponse
    {
        $validatedData = $request->validated();
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instance_id)->first();
        $isSuccessfull = true;

        // Avoid duplicated notifications if request repeated for already successful update
        if ($instance->hasPendingUpdateRequest('core')) {
            $isSuccessfull = $isSuccessfull && $this->statusUpdate($instance, $validatedData);
            $this->notify($validatedData, $instance);

            // Sync instances available core versions
            if ($isSuccessfull) {
                Sync::dispatch($instance, CoreSyncType::TYPE, 'Core sync failed!');
            }
        }

        if (! $isSuccessfull) {
            return response()->json([
                'message' => 'There was and error while updating the instance core status. Please try again or contact support.',
                'status' => false,
            ], 500);
        }

        return response()->json([
            'message' => 'Core update statuses received successfully.',
            'status' => true,
        ]);
    }

    /**
     * Notifies the user about the plugin updates
     *
     * The notification will be sent to the user with the given user_id
     * Notification contains link to instance dashboard where user can see update request status in detail
     *
     * @param  mixed  $validatedData
     */
    private function notify(array $validatedData, Instance $instance): void
    {
        $status = $validatedData['status'] ? 'success' : 'danger';
        $update = Update::find($validatedData['update_id']);

        $title = $validatedData['status'] ? __('Core update successfull') : __('Core update failed');
        $message = $validatedData['status'] ?
            __('Core update for instance :instance was successfully. Applied relase :release for version :version.', ['instance' => $instance->name, 'release' => $update->release, 'version' => $update->version]) :
            __('Core update for instance :instance failed. Error message: :message', ['instance' => $instance->name, 'message' => $validatedData['message']]);

        Notification::make()
            ->status($status)
            ->title($title)
            ->body($message)
            ->actions([
                Action::make('view')
                    ->color($status)
                    ->button()
                    ->url(route('filament.app.pages.app-dashboard', ['tenant' => $instance]), shouldOpenInNewTab: true),
                Action::make('cancel')
                    ->color('secondary')
                    ->close(),
            ])
            ->icon('fas-cube')
            ->iconColor($status)
            ->sendToDatabase(User::find($validatedData['user_id']));
    }

    /**
     * Updates the status of the update request and its items
     *
     * @return void
     */
    private function statusUpdate(Instance $instance, array $data): bool
    {
        $status = true;

        try {
            // update parent UpdateRequest status
            $updateRequest = UpdateRequest::where('instance_id', $instance->id)
                ->where('type', UpdateRequest::TYPE_CORE)
                ->where('status', UpdateRequest::STATUS_PENDING)
                ->first();

            $updateRequest->update([
                'status' => $data['status'],
                'moodle_job_id' => $data['moodle_job_id'],
            ]);

            UpdateRequestItem::where('update_request_id', $updateRequest->id)
                ->update([
                    'status' => $data['status'],
                    'error' => $data['message'],
                ]);
        } catch (Exception $e) {
            Log::error(__FILE__.':'.__LINE__.' - '.'Failed to update Core instance update_request status for instance: '.$instance->name.' Error message: '.$e->getMessage());

            $status = false;
        }

        return $status;
    }
}
