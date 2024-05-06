<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Filament\Admin\Clusters\Updates;
use App\Filament\Admin\Resources\InstanceResource;
use App\Models\Plugin;
use App\Models\Update;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class ChooseUpdateTypePage extends Page
{
    protected static ?string $cluster = Updates::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.admin.pages.choose-update-type-page';

    protected static ?string $title = 'Choose update type';

    public int $currentStep = 3;

    public string|array|null $instanceIds;

    public string|array|null $clusterIds;

    public ?string $updateType = null;

    public function mount(): void
    {
        $this->clusterIds = unserialize(urldecode(request('clusterIds')));
        $this->instanceIds = unserialize(urldecode(request('instanceIds')));
        $updateType = request('updateType');

        if (! is_array($this->clusterIds) || empty($this->clusterIds)) {
            $this->redirect(ChooseClusterPage::getUrl());
        }
        if (! is_array($this->instanceIds) || empty($this->instanceIds)) {
            $this->redirect(ChooseInstancePage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
            ]));
        }
        if ($updateType != null) {
            $this->updateType = $updateType;
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
                    'url' => '#',
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

    public function getUpdateTypes(): array
    {
        //@todo: Api response should also return you update type(minor core update, major core update, plugin)!
        return [
            [
                'type' => 'plugin',
                'text' => __('Plugin update'),
                'count' => Plugin::whereHas('updates', function ($q) {
                    $q->whereIn('updates.instance_id', $this->instanceIds);
                })->count(),
            ],
            [
                'type' => 'minor-core',
                'text' => __('Minor instance update'),
                'count' => Update::whereIn('instance_id', $this->instanceIds)->whereNull('plugin_id')->distinct('version')->count(),
            ],
            [
                'type' => 'major-core',
                'text' => __('Major instance update'),
                'count' => Update::whereIn('instance_id', $this->instanceIds)->whereNull('plugin_id')->distinct('version')->count(),
            ],
        ];
    }

    private function getUpdateTypeCount(string $type): int
    {
        foreach ($this->getUpdateTypes() as $updateType) {
            if($updateType['type'] === $type) {
                return $updateType['count'];
            }
        }

        return 0;
    }

    public function selectUpdateType(?string $type): void
    {
        if ($type !== $this->updateType) {
            $this->updateType = $type;
        } else {
            $this->updateType = null;
        }
    }

    public function isSelected(?string $type): bool
    {
        return $type === $this->updateType;
    }

    public function goToNextStep(): void
    {
        if ($this->updateType === null) {
            Notification::make()
                ->danger()
                ->title(__('You should select update type.'))
                ->send();

            return;
        } elseif ($this->getUpdateTypeCount($this->updateType) === 0) {
            Notification::make()
                ->info()
                ->title(__('There are no new updates for selected update type.'))
                ->send();

            return;
        }

        //@todo: update based on the diff between minor and major core update!
        $redirectPage = match ($this->updateType) {
            'minor-core', 'major-core' => InstanceCoreUpdatesPage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
                'updateType' => $this->updateType,
            ]),
            'plugin' => PluginUpdatesPage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
                'updateType' => $this->updateType,
            ]),
        };

        $this->redirect($redirectPage);
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseInstancePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
        ]));
    }
}
