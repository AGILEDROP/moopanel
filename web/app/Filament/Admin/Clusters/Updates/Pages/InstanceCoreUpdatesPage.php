<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Models\Instance;
use App\Models\Update;
use Illuminate\Database\Eloquent\Collection;

class InstanceCoreUpdatesPage extends BaseUpdateWizardPage
{
    protected static string $view = 'filament.admin.pages.instance-core-updates-page';

    protected static ?string $title = 'Core update';

    protected static ?string $slug = 'core';

    public int $currentStep = 4;

    public bool $hasUpdateAllAction = true;

    public function getRecords(): Collection|array
    {
        $records = Update::select('id', 'maturity', 'version', 'release', 'url')
            ->whereIn('instance_id', $this->instanceIds)
            ->whereNull('plugin_id')
            ->where('type', $this->updateType)
            ->distinct('release')
            ->orderBy('release', 'desc')
            ->get();

        $records->map(function ($update) {
            $updateInstances = Instance::whereIn('id',
                Update::whereIn('instance_id', $this->instanceIds)
                    ->whereNull('plugin_id')
                    ->where('type', $this->updateType)
                    ->where('release', $update->release)
                    ->pluck('instance_id')
                    ->toArray()
            )->get();
            $update['date'] = $update->version_date;
            $update['instances'] = $updateInstances;
        });

        return $records;
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseUpdateTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
            'updateType' => $this->updateType,
        ]));
    }

    // todo: implement update action logic when update trigger endpoint will be provided (not yet)!
    public function update(int $updateId): void
    {
        dd("Run update with id: {$updateId} on all instances!");
    }

    public function updateAll(): void
    {
        dd('Implement update all logic!');
    }
}
