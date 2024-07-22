<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Jobs\Update\CoreUpdateRequestJob;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Models\Update;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Collection;

class InstanceCoreUpdatesPage extends BaseUpdateWizardPage implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;

    protected static string $view = 'filament.admin.pages.instance-core-updates-page';

    protected static ?string $title = 'Core update';

    protected static ?string $slug = 'core';

    public int $currentStep = 4;

    public bool $hasUpdateAllAction = false;

    public function updateAction(): Action
    {
        return Action::make('update')
            ->label(__('Update'))
            ->requiresConfirmation()
            ->modalDescription(__('Are you sure you want to perform the core update of the selected instance?'))
            ->modalIcon('fas-cube')
            ->icon('fas-cube')
            ->action(function (array $arguments) {
                $update = Update::find($arguments['updateid']);

                $instance = $update->instance;
                $user = auth()->user();

                if (! $update || ! $user) {
                    Notification::make()
                        ->danger()
                        ->title(__('Update not found'))
                        ->send();

                    return;
                }

                $payload = [
                    'user_id' => $user->id,
                    'instance_id' => $update->instance_id,
                    'update_id' => $update->id,
                    'type' => 'core',
                    'version' => $update->version,
                    'release' => $update->release,
                    'url' => $update->url,
                    'download' => $update->download,
                ];

                CoreUpdateRequestJob::dispatch($instance, $update, $user, $payload);

                Notification::make()
                    ->success()
                    ->title(__('Core update request submitted'))
                    ->icon('fas-cube')
                    ->iconColor('success')
                    ->send();
            });
    }

    public function getRecords(): Collection|array
    {
        $records = Update::select('id', 'instance_id', 'maturity', 'version', 'release', 'url')
            ->whereIn('instance_id', $this->instanceIds)
            ->whereNull('plugin_id')
            ->where('type', $this->type)
            //->distinct('release')
            ->orderBy('release', 'desc')
            ->get();

        $records->map(function ($update) {
            $updateInstances = Instance::withoutGlobalScope(InstanceScope::class)
                ->where('id', $update->instance_id)
                ->get();

            $update['date'] = $update->version_date->toDateString();
            $update['instances'] = $updateInstances;
        });

        return $records;
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseUpdateTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
            'type' => $this->type,
        ]));
    }
}
