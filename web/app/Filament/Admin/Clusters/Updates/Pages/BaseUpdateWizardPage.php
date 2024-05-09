<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Enums\UpdateType;
use App\Filament\Admin\Clusters\Updates;
use App\Filament\Admin\Resources\InstanceResource;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class BaseUpdateWizardPage extends Page
{
    protected static ?string $cluster = Updates::class;

    protected static bool $shouldRegisterNavigation = false;

    public int $currentStep = 1;

    public array $records = [];

    public string|array|null $instanceIds;

    public string|array|null $clusterIds;

    public ?string $updateType = null;

    public bool $hasUpdateAllAction = false;

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
                    'updateType' => $this->updateType,
                ]),
                4 => '#',
            }
        };
    }

    public function mount(): void
    {
        $this->mountClusterIds();
        $this->mountInstanceIds();
        $this->mountUpdateType();
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
        } elseif ($this->currentStep > 2) {
            $this->instanceIds = unserialize(urldecode(request('instanceIds')));
            if (! is_array($this->instanceIds) || empty($this->instanceIds)) {
                $this->redirect(ChooseInstancePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                ]));
            }
        }
    }

    protected function mountUpdateType(): void
    {
        if ($this->currentStep === 3) {
            if (request('updateType') != null && UpdateType::tryFrom(request('updateType')) != null) {
                $this->updateType = request('updateType');
            }
        } elseif ($this->currentStep > 3) {
            $this->updateType = request('updateType');
            if ($this->updateType === null || UpdateType::tryFrom($this->updateType) === null) {
                $this->redirect(ChooseUpdateTypePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                ]));
            }
        }
    }
}
