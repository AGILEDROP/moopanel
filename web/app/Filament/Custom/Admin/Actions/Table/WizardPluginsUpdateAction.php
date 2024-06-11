<?php

namespace App\Filament\Custom\Admin\Actions\Table;

use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Update;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\HtmlString;

class WizardPluginsUpdateAction
{
    public static function make(string $name, array $instanceIds, array $refreshComponents): Action
    {
        // TODO: use combination of trigger and post request to avoid timeouts!
        return Action::make($name)
            ->label(__('Update'))
            ->iconButton()
            ->icon('heroicon-o-arrow-up-circle')
            ->modalHeading(__('Choose update'))
            ->form([
                Select::make('update_release')
                    ->label(__('Release'))
                    ->options(fn (Plugin $record) => $record
                        ->updates()
                        ->whereIn('instance_id', $instanceIds)
                        ->distinct('release')
                        ->pluck('release', 'release')
                        ->toArray()
                    )
                    ->rules(['required', 'string', 'exists:updates,release']),
            ])
            ->action(function (Plugin $record, array $data) use ($instanceIds) {
                $moduleApiService = new ModuleApiService();
                $resultsCount = 0;
                $successfulUpdatesCount = 0;
                $unsuccessfulUpdatesCount = 0;
                $notificationBody = null;

                // Get plugin updates
                $updateIds = $record->updates()
                    ->whereIn('instance_id', $instanceIds)
                    ->where('release', $data['update_release'])
                    ->pluck('id');

                // Get instances you need to loop on!
                $distinctInstanceIds = Update::whereIn('id', $updateIds)
                    ->distinct('instance_id')
                    ->pluck('instance_id');
                $instances = Instance::whereIn('id', $distinctInstanceIds)->get();
                foreach ($instances as $instance) {
                    $triggerSync = false;
                    $selectedUpdates = $instance->updates()
                        ->where('plugin_id', $record->id)
                        ->whereIn('id', $updateIds)
                        ->get();

                    if (count($selectedUpdates) > 0) {
                        $updatesData = [];
                        foreach ($selectedUpdates as $selectedUpdate) {
                            $updatesData[] = [
                                'model_id' => $selectedUpdate->id,
                                'component' => $record->component,
                                'version' => $selectedUpdate->version,
                                'release' => $selectedUpdate->release,
                                'download' => $selectedUpdate->download,
                            ];
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
                                $triggerSync = true;
                                $successfulUpdatesCount++;
                                $notificationBody .= new HtmlString('<b>'.$instance->name.'</b>: '.__('successfully updated to :release', ['release' => $data['update_release']]).'<br/>');
                            } else {
                                $unsuccessfulUpdatesCount++;
                                $notificationBody .= new HtmlString('<b>'.$instance->name.'</b>: '.__('update to :release failed! Check <a class="text-primary-500 font-medium" href="'.route('filament.app.resources.update-logs.index', ['tenant' => $instance->id]).'" target="_blank">update log</a> for more information.', ['release' => $data['update_release']]).'<br/>');
                            }
                        }

                        // Sync plugins data if at least one update was successful.
                        if ($triggerSync) {
                            $syncType = SyncTypeFactory::create(PluginsSyncType::TYPE, $instance);
                            $syncType->run(true);
                        }
                    }
                }

                // Display result to user.
                if ($successfulUpdatesCount === $resultsCount && $successfulUpdatesCount > 0) {
                    Notification::make()
                        ->success()
                        ->title(__('Plugins successfully updated to latest chosen release on all instances.'))
                        ->send();
                } elseif ($unsuccessfulUpdatesCount > 0) {
                    Notification::make()
                        ->warning()
                        ->title(__(':numSuccessfully plugins was successfully updated and :numUnsuccessfully update failed!', [
                            'numSuccessfully' => $successfulUpdatesCount,
                            'numUnsuccessfully' => $unsuccessfulUpdatesCount,
                        ]))
                        ->body($notificationBody)
                        ->send();
                } else {
                    Notification::make()
                        ->warning()
                        ->title(__('Plugin update failed! Check instances update log for more information.'))
                        ->send();
                }
            })->after(function ($livewire) use ($refreshComponents) {
                if (! empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            });
    }
}
