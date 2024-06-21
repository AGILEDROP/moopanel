<?php

namespace App\Filament\Custom\App\Actions\Table;

use App\Enums\UpdateMaturity;
use App\Jobs\Update\PluginUpdateJob;
use App\Models\Instance;
use App\Models\Update;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class UpdatePluginsBulkAction
{
    public static function make(string $name, array $refreshComponents): BulkAction
    {
        return BulkAction::make($name)
            ->label(__('Update to latest stable version'))
            ->icon('heroicon-o-arrow-up-circle')
            ->action(function (Collection $records) {
                $instance = filament()->getTenant();
                $user = Auth::user();

                $payload = [
                    'user_id' => $user->id,
                    'username' => $user->email,
                    'instance_id' => $instance->id,
                    'updates' => []
                ];

                foreach ($records as $record) {
                    $latestStableUpdate = $record->updates()
                        ->where('maturity', UpdateMaturity::STABLE)
                        ->orderBy('version', 'desc')
                        ->first();
                    if (!$latestStableUpdate) {
                        Notification::make()
                            ->danger()
                            ->persistent()
                            ->title(__('Failed! :plugin doesn\'t have any available stable updates.', ['plugin' => $record->plugin->display_name]))
                            ->body(__('Please check if all selected plugins have available updates, before you try again.'))
                            ->send();

                        return;
                    }

                    $payload['updates'][] = [
                        'model_id' => $latestStableUpdate->id,
                        'component' => $record->plugin->component,
                        'version' => $latestStableUpdate->version,
                        'release' => $latestStableUpdate->release,
                        'download' => $latestStableUpdate->download,
                    ];
                }

                PluginUpdateJob::dispatch($instance, Auth::user(), $payload);

                Notification::make()
                    ->success()
                    ->title(__('Plugin updates have started'))
                    ->body(__('Plugin updates have started. It might take a while. Check notifications for progress.'))
                    ->seconds(7)
                    ->send();
            })
            ->after(function ($livewire) use ($refreshComponents) {
                if (!empty($refreshComponents)) {
                    foreach ($refreshComponents as $refreshComponent) {
                        $livewire->dispatch($refreshComponent);
                    }
                }
            });
    }
}
