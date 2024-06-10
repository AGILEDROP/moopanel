<?php

namespace App\Filament\Custom\App\Actions\Table;

use App\Models\Instance;
use App\Models\InstancePlugin;
use App\Models\Update;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class UpdatePluginAction
{
    public static function make(string $name, array $refreshComponents): Action
    {
        // TODO: use combination of trigger and post request to avoid timeouts!
        return Action::make($name)
            ->label(__('Update'))
            ->hidden(fn (Model $record): bool => ! $record->updates_exists)
            ->iconButton()
            ->icon('heroicon-o-arrow-up-circle')
            ->modalHeading(__('Choose update'))
            ->form([
                Select::make('update_id')
                    ->label(__('Release'))
                    ->options(fn (InstancePlugin $record) => Update::where('plugin_id', $record->plugin_id)
                        ->pluck('release', 'id')
                        ->toArray()
                    )
                    ->rules(['required', 'integer', 'exists:updates,id']),
            ])
            ->action(function (InstancePlugin $record, array $data) {
                try {
                    $moduleApiService = new ModuleApiService();
                    $instance = filament()->getTenant();
                    $selectedUpdate = $record->updates()->where('updates.id', $data['update_id'])->first();
                    $postData[] = [
                        'model_id' => (int) $data['update_id'],
                        'component' => $record->plugin->component,
                        'version' => $selectedUpdate->version,
                        'release' => $selectedUpdate->release,
                        'download' => $selectedUpdate->download,
                    ];

                    $response = $moduleApiService->triggerPluginsUpdates($instance->url, Crypt::decrypt($instance->api_key), $postData);
                    if (! $response->ok()) {
                        throw new \Exception('Plugin update failed with status code: '.$response->status().'.');
                    }

                    $success = ($response->json('updates.0.'.$data['update_id'].'.status') === true);
                    if (! $success) {
                        throw new \Exception('Plugin update unsuccessful with status code: '.$response->status().'!');
                    } else {
                        // Sync plugin data!
                        $syncType = SyncTypeFactory::create(PluginsSyncType::TYPE, Instance::find(filament()->getTenant()->id));
                        $syncType->run(true);
                    }

                    Notification::make()
                        ->success()
                        ->title('Plugin successfully updated to release '.$selectedUpdate->release.' (version: '.$selectedUpdate->version.').')
                        ->send();
                } catch (\Exception $exception) {
                    Log::error($exception->getMessage());

                    Notification::make()
                        ->danger()
                        ->title(__('Plugin: :plugin update failed!', ['plugin' => $record->plugin->display_name]))
                        ->body(__('Please contact administrator for more information'))
                        ->send();
                }
            })
            ->after(function ($livewire) use ($refreshComponents) {
                if (! empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            });
    }
}
