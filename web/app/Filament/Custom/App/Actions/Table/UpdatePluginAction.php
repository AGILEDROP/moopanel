<?php

namespace App\Filament\Custom\App\Actions\Table;

use App\Jobs\Update\PluginUpdateJob;
use App\Models\InstancePlugin;
use App\Models\Update;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class UpdatePluginAction
{
    public static function make(string $name, array $refreshComponents): Action
    {
        return Action::make($name)
            ->label(__('Update'))
            ->hidden(fn (Model $record): bool => ! $record->updates_exists)
            ->iconButton()
            ->icon('heroicon-o-arrow-up-circle')
            ->modalHeading(__('Choose update'))
            ->form([
                Select::make('update_id')
                    ->label(__('Release'))
                    ->options(
                        fn (InstancePlugin $record) => Update::where('plugin_id', $record->plugin_id)
                            ->pluck('release', 'id')
                            ->toArray()
                    )
                    ->rules(['required', 'integer', 'exists:updates,id']),
            ])
            ->action(function (InstancePlugin $record, array $data) {
                $instance = filament()->getTenant();
                $selectedUpdate = $record->updates()->where('updates.id', $data['update_id'])->first();
                $user = Auth::user();

                $payload = [
                    'user_id' => $user->id,
                    'username' => $user->email,
                    'instance_id' => $instance->id,
                    'updates' => [
                        [
                            'model_id' => (int) $data['update_id'],
                            'component' => $record->plugin->component,
                            'version' => $selectedUpdate->version,
                            'release' => $selectedUpdate->release,
                            'download' => $selectedUpdate->download,
                        ],
                    ],
                ];

                PluginUpdateJob::dispatch($instance, Auth::user(), $payload);

                Notification::make()
                    ->success()
                    ->title(__('Plugin update has started'))
                    ->body(__('Plugin update has started. It might take a while. Check notifications for progress.'))
                    ->seconds(7)
                    ->send();
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
