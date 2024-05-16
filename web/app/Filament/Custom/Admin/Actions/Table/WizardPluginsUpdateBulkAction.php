<?php

namespace App\Filament\Custom\Admin\Actions\Table;

use App\Enums\UpdateMaturity;
use App\Models\Instance;
use App\Services\ModuleApiService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;

class WizardPluginsUpdateBulkAction
{
    public static function make(string $name, array $instanceIds): BulkAction
    {
        return BulkAction::make($name)
            ->label(__('Update to last stable version'))
            ->icon('heroicon-o-arrow-up-circle')
            ->action(function (Collection $records) use ($instanceIds) {
                $moduleApiService = new ModuleApiService();
                $resultsCount = 0;
                $successfulUpdatesCount = 0;

                // Get all instances
                $instances = Instance::whereIn('id', $instanceIds)->get();
                foreach ($instances as $instance) {
                    $updatesData = [];
                    foreach ($records as $record) {
                        $latestStablePluginUpdateOnInstance = $record->updates()
                            ->where('instance_id', $instance->id)
                            ->where('maturity', UpdateMaturity::STABLE)
                            ->orderBy('version', 'desc')
                            ->first();

                        if ($latestStablePluginUpdateOnInstance) {
                            $updatesData[] = [
                                'model_id' => $latestStablePluginUpdateOnInstance->id,
                                'component' => $record->component,
                                'version' => $latestStablePluginUpdateOnInstance->version,
                                'release' => $latestStablePluginUpdateOnInstance->release,
                            ];
                        }
                    }

                    // Run update action and show the response!
                    $response = $moduleApiService->triggerPluginsUpdates($instance->url, Crypt::decrypt($instance->api_key), $updatesData);
                    if (! $response->ok()) {
                        throw new \Exception('Plugin update failed with status code: '.$response->status().'.');
                    }
                    $results = $response->json('updates');
                    $resultsCount += count($results);

                    foreach ($results as $pluginUpdateResult) {
                        $key = key($pluginUpdateResult);
                        $values = $pluginUpdateResult[$key];
                        if ($values['status']) {
                            $successfulUpdatesCount++;
                        }
                    }
                }

                // todo: currently endpoint doesnt execute update! Check when endpoint will be prepared!
                if ($successfulUpdatesCount === $resultsCount) {
                    Notification::make()
                        ->success()
                        ->title(__('Plugins successfully updated to latest version on all selected instances.'))
                        ->send();
                } else {
                    Notification::make()
                        ->warning()
                        ->title(__('Bulk plugin update failed! Check instances update log for more information.'))
                        ->send();
                }
            });
    }
}
