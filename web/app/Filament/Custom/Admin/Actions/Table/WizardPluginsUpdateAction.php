<?php

namespace App\Filament\Custom\Admin\Actions\Table;

use App\Jobs\Update\PluginUpdateJob;
use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Update;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\HtmlString;

class WizardPluginsUpdateAction
{
    public static function make(string $name, array $instanceIds, array $refreshComponents): Action
    {
        return Action::make($name)
            ->label(__('Update'))
            ->iconButton()
            ->icon('heroicon-o-arrow-up-circle')
            ->modalHeading(__('Choose update'))
            ->form([
                Select::make('update_release')
                    ->label(__('Release'))
                    ->options(
                        fn (Plugin $record) => $record
                            ->updates()
                            ->whereIn('instance_id', $instanceIds)
                            ->distinct('release')
                            ->pluck('release', 'release')
                            ->toArray()
                    )
                    ->rules(['required', 'string', 'exists:updates,release']),
            ])
            ->action(function (Plugin $record, array $data) use ($instanceIds) {
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

                $user = Auth::user();
                foreach ($instances as $instance) {
                    $selectedUpdates = $instance->updates()
                        ->where('plugin_id', $record->id)
                        ->whereIn('id', $updateIds)
                        ->get();


                    $payload = [
                        'user_id' => $user->id,
                        'username' => $user->email,
                        'instance_id' => $instance->id,
                        'updates' => []
                    ];

                    if (count($selectedUpdates) > 0) {
                        foreach ($selectedUpdates as $selectedUpdate) {
                            $payload['updates'][] = [
                                'model_id' => $selectedUpdate->id,
                                'component' => $record->component,
                                'version' => $selectedUpdate->version,
                                'release' => $selectedUpdate->release,
                                'download' => $selectedUpdate->download,
                            ];
                        }

                        PluginUpdateJob::dispatch($instance, Auth::user(), $payload);
                    }
                }

                Notification::make()
                    ->success()
                    ->title(__('Available plugin updates started'))
                    ->body(__('Plugin updates have been started. It might take a while. Check notifications for progress.'))
                    ->seconds(7)
                    ->send();

            })->after(function ($livewire) use ($refreshComponents) {
                if (!empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            });
    }
}
