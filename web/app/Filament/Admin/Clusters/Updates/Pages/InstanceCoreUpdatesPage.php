<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Filament\Admin\Clusters\Updates;
use App\Filament\Admin\Resources\InstanceResource;
use App\Models\Instance;
use App\Models\Update;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\HtmlString;

class InstanceCoreUpdatesPage extends Page
{
    protected static ?string $cluster = Updates::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.admin.pages.instance-core-updates-page';

    protected static ?string $title = 'Instance update';

    public int $currentStep = 4;

    public string|array|null $instanceIds;

    public string|array|null $clusterIds;

    public bool $hasUpdateAllAction = true;

    public ?string $updateType = null;

    public function mount(): void
    {
        $this->clusterIds = unserialize(urldecode(request('clusterIds')));
        $this->instanceIds = unserialize(urldecode(request('instanceIds')));
        $this->updateType = request('updateType');

        if (! is_array($this->clusterIds) || empty($this->clusterIds)) {
            $this->redirect(ChooseClusterPage::getUrl());
        }
        if (! is_array($this->instanceIds) || empty($this->instanceIds)) {
            $this->redirect(ChooseInstancePage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
            ]));
        }
        // @todo: check if update type is in enum else return back to update page!
        if ($this->updateType === null) {
            $this->redirect(ChooseUpdateTypePage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
            ]));
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            ChooseClusterPage::getUrl() => __('Updates'),
        ];
    }

    protected function getTableWizardHeaderData(): ?array
    {
        return [
            'steps' => [
                [
                    'name' => 'Choose cluster',
                    'url' => ChooseClusterPage::getUrl([
                        'clusterIds' => urlencode(serialize($this->clusterIds)),
                    ]),
                    'step' => 1,
                ],
                [
                    'name' => 'Choose instances',
                    'url' => ChooseInstancePage::getUrl([
                        'clusterIds' => urlencode(serialize($this->clusterIds)),
                        'instanceIds' => urlencode(serialize($this->instanceIds)),
                    ]),
                    'step' => 2,
                ],
                [
                    'name' => 'Choose update type',
                    'url' => ChooseUpdateTypePage::getUrl([
                        'clusterIds' => urlencode(serialize($this->clusterIds)),
                        'instanceIds' => urlencode(serialize($this->instanceIds)),
                    ]),
                    'step' => 3,
                ],
                [
                    'name' => 'Update',
                    'url' => '#',
                    'step' => 4,
                ],
            ],
        ];
    }

    public function getRecords(): Collection|array
    {
        $records = Update::select('version', 'release', 'url')
            ->whereIn('instance_id', $this->instanceIds)
            ->whereNull('plugin_id')
            ->distinct('version')
            ->orderBy('version', 'desc')
            ->get();

        $records->map(function ($update) {
            $updateInstances = Instance::whereIn('id',
                Update::whereIn('instance_id', $this->instanceIds)
                    ->whereNull('plugin_id')
                    ->where('version', $update->version)
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

    //    public function submit(): void
    //    {
    //        $this->updateAll();
    //    }

    public function submitAction(): Action
    {
        return $this->updateAll();
    }

    public function updateAll()
    {
        dd('Implement update logic!');
        //@todo: write logic to run all core updates!
    }
}
