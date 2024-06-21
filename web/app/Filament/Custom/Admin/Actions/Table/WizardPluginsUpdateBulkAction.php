<?php

namespace App\Filament\Custom\Admin\Actions\Table;

use App\Enums\UpdateMaturity;
use App\Jobs\Update\PluginUpdateJob;
use App\Models\Instance;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

class WizardPluginsUpdateBulkAction
{
    public static function make(string $name, array $instanceIds, array $refreshComponents): BulkAction
    {
        return BulkAction::make($name)
            ->label(__('Update to last stable version'))
            ->icon('heroicon-o-arrow-up-circle')
            ->action(function (Collection $records) use ($instanceIds) {
                // Get all instances
                $instances = Instance::whereIn('id', $instanceIds)->get();
                $user = Auth::user();

                foreach ($instances as $instance) {

                    $payload = [
                        'user_id' => $user->id,
                        'username' => $user->email,
                        'instance_id' => $instance->id,
                        'updates' => []
                    ];

                    foreach ($records as $record) {
                        $latestStablePluginUpdateOnInstance = $record->updates()
                            ->where('instance_id', $instance->id)
                            ->where('maturity', UpdateMaturity::STABLE)
                            ->orderBy('version', 'desc')
                            ->first();

                        if ($latestStablePluginUpdateOnInstance) {
                            $payload['updates'][] = [
                                'model_id' => $latestStablePluginUpdateOnInstance->id,
                                'component' => $record->component,
                                'version' => $latestStablePluginUpdateOnInstance->version,
                                'release' => $latestStablePluginUpdateOnInstance->release,
                                'download' => $latestStablePluginUpdateOnInstance->download,
                            ];
                        }
                    }

                    PluginUpdateJob::dispatch($instance, Auth::user(), $payload);
                }

                Notification::make()
                    ->success()
                    ->title(__('Available plugin updates started'))
                    ->body(__('Plugin updates have been started. It might take a while. Check notifications for progress.'))
                    ->seconds(7)
                    ->send();
            });
    }
}
