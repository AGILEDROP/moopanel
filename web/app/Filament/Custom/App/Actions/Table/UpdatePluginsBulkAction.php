<?php

namespace App\Filament\Custom\App\Actions\Table;

use App\Enums\UpdateMaturity;
use App\Models\Update;
use App\Services\ModuleApiService;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class UpdatePluginsBulkAction
{
    public static function make(string $name): BulkAction
    {
        return BulkAction::make($name)
            ->label(__('Update to latest stable version'))
            ->icon('heroicon-o-arrow-up-circle')
            ->action(function (Collection $records) {
                try {
                    $moduleApiService = new ModuleApiService();
                    $instance = filament()->getTenant();
                    $updatesData = [];

                    foreach ($records as $record) {
                        $latestStableUpdate = $record->updates()
                            ->where('maturity', UpdateMaturity::STABLE)
                            ->orderBy('version', 'desc')
                            ->first();
                        if (! $latestStableUpdate) {
                            Notification::make()
                                ->danger()
                                ->persistent()
                                ->title(__('Failed! :plugin doesn\'t have any available stable updates.', ['plugin' => $record->plugin->display_name]))
                                ->body(__('Please check if all selected plugins have available updates, before you try again.'))
                                ->send();

                            return;
                        }

                        $updatesData[] = [
                            'model_id' => $latestStableUpdate->id,
                            'component' => $record->plugin->component,
                            'version' => $latestStableUpdate->version,
                            'release' => $latestStableUpdate->release,
                        ];
                    }

                    $response = $moduleApiService->triggerPluginsUpdates($instance->url, Crypt::decrypt($instance->api_key), $updatesData);
                    if (! $response->ok()) {
                        throw new \Exception('Plugins update to latest stable version failed with status code: '.$response->status().'.');
                    }
                    $results = $response->json('updates');

                    $successfulUpdates = [];
                    $unsuccessfulUpdates = [];
                    foreach ($results as $pluginUpdateResult) {
                        $key = key($pluginUpdateResult);
                        $values = $pluginUpdateResult[$key];
                        if ($values['status']) {
                            $successfulUpdates[] = $key;
                        } else {
                            $unsuccessfulUpdates[] = $key;
                        }
                    }

                    // todo: currently endpoint doesnt execute update! Check when endpoint will be prepared!
                    if (count($successfulUpdates) === 0) {
                        Notification::make()
                            ->danger()
                            ->title(__('None of selected plugins was updated successfully!'))
                            ->send();
                    } elseif (count($successfulUpdates) === count($results)) {
                        Notification::make()
                            ->success()
                            ->title(__('All selected plugins successfully updated to latest stable version.'))
                            ->send();
                    } elseif (count($unsuccessfulUpdates) > 0) {
                        Notification::make()
                            ->warning()
                            ->title(__(':numSuccessfully plugins was successfully updated and :numUnsuccessfully update failed!', [
                                'numSuccessfully' => count($successfulUpdates),
                                'numUnsuccessfully' => count($unsuccessfulUpdates),
                            ]))
                            ->body(__('Check update log for more information.'))
                            ->persistent()
                            ->send();
                    }
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());

                    Notification::make()
                        ->danger()
                        ->title(__('Plugins update to latest stable version failed!'))
                        ->body(__('Please contact administrator for more information.'))
                        ->send();
                }

            });
    }
}
