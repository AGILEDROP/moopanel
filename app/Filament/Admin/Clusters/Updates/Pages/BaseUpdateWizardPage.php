<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Enums\UpdateType;
use App\Filament\Admin\Clusters\Updates\UpdatesCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Pages\Page;
use Filament\Schemas\Components\Icon;

class BaseUpdateWizardPage extends Page
{
    protected static ?string $cluster = UpdatesCluster::class;

    protected static bool $shouldRegisterNavigation = false;

    public int $currentStep = 1;

    public array $records = [];

    public string|array|null $instanceIds;

    public string|array|null $clusterIds;

    public ?string $type = null;

    public bool $hasUpdateAllAction = false;

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new Icon('heroicon-o-home'),
            ChooseClusterPage::getUrl() => __('Updates'),
        ];
    }

    protected function getTableWizardHeaderData(): ?array
    {
        return [
            'steps' => [
                [
                    'name' => __('Choose cluster'),
                    'url' => $this->getStepUrl(1),
                    'step' => 1,
                ],
                [
                    'name' => __('Choose instances'),
                    'url' => $this->getStepUrl(2),
                    'step' => 2,
                ],
                [
                    'name' => __('Choose update type'),
                    'url' => $this->getStepUrl(3),
                    'step' => 3,
                ],
                [
                    'name' => __('Update'),
                    'url' => $this->getStepUrl(4),
                    'step' => 4,
                ],
            ],
        ];
    }

    protected function getStepUrl(int $step)
    {
        return match ($this->currentStep) {
            1 => function ($step) {
                // In first step all urls are disabled!
                return '#';
            },
            2 => match ($step) {
                1 => ChooseClusterPage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                ]),
                2, 3, 4 => '#',
            },
            3 => match ($step) {
                1 => ChooseClusterPage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                ]),
                2 => ChooseInstancePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                ]),
                3, 4 => '#',
            },
            4 => match ($step) {
                1 => ChooseClusterPage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                ]),
                2 => ChooseInstancePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                ]),
                3 => ChooseUpdateTypePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                    'type' => $this->type,
                ]),
                4 => '#',
            }
        };
    }

    public function mount(): void
    {
        $this->mountClusterIds();
        $this->mountInstanceIds();
        $this->mountType();
    }

    protected function mountClusterIds(): void
    {
        if ($this->currentStep == 1) {
            if (request()->has('clusterIds')) {
                $this->records = unserialize(urldecode(request('clusterIds')));
            }
        } else {
            $this->clusterIds = unserialize(urldecode(request('clusterIds')));
            if (! is_array($this->clusterIds) || empty($this->clusterIds)) {
                $this->redirect(ChooseClusterPage::getUrl());
            }
        }
    }

    protected function mountInstanceIds(): void
    {
        if ($this->currentStep === 2) {
            if (request()->has('instanceIds')) {
                $this->records = unserialize(urldecode(request('instanceIds')));
            }
        } else if ($this->currentStep > 2) {
            $this->instanceIds = unserialize(urldecode(request('instanceIds')));
            if (! is_array($this->instanceIds) || empty($this->instanceIds)) {
                $this->redirect(ChooseInstancePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                ]));
            }
        }
    }

    protected function mountType(): void
    {
        if ($this->currentStep === 3) {
            if (request('type') != null && (UpdateType::tryFrom(request('type')) != null || request('type') != 'zip')) {
                $this->type = request('type');
            }
        } else if ($this->currentStep > 3) {
            $this->type = request('type');
            if ($this->type === null || (UpdateType::tryFrom($this->type) === null && $this->type != 'zip')) {
                $this->redirect(ChooseUpdateTypePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                ]));
            }
        }
    }
}
