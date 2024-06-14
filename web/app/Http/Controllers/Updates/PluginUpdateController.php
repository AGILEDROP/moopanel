<?php

namespace App\Http\Controllers\Updates;

use App\Filament\App\Pages\AppDashboard;
use App\Http\Controllers\Controller;
use App\Http\Requests\Update\PluginUpdateCreate;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\User;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PluginUpdateController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  PluginUpdateCreate  $request
     * @param  int  $instance_id
     * @return JsonResponse
     */
    public function store(PluginUpdateCreate $request, $instance_id): JsonResponse
    {
        $validatedData = $request->validated();
        $instance = Instance::withoutGlobalScope(InstanceScope::class)->where('id', (int) $instance_id)->first();

        // TODO: update updateRequest general status and updateRequestItems statuses

        $this->notify($validatedData, $instance);

        // TODO: razmisli, ali bi syncali na interval, namesto na request
        $syncType = SyncTypeFactory::create(PluginsSyncType::TYPE, $instance);
        $syncType->run();

        return response()->json([
            'message' => 'Plugin update statuses received successfully.',
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
            ->body(__(':count plugins for instance :instance_name have been updated.', ['count' => $successfullUpdates . '/' . $allUpdates, 'instance_name' => $instance->name]))
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
        $message = 'Instance plugins updated successfully';

        if ($successfulUpdates === 0) {
            $message = 'Instance plugins update failed';
        } elseif ($successfulUpdates < $allUpdates) {
            $message = 'Instance plugins update partially succeeded';
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
