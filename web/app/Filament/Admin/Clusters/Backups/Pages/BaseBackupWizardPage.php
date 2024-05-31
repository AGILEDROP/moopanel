<?php

namespace App\Filament\Admin\Clusters\Backups\Pages;

use App\Enums\BackupType;
use App\Filament\Admin\Clusters\Backups;
use App\Filament\Admin\Resources\InstanceResource;
use Filament\Pages\Page;
use Illuminate\Support\HtmlString;

class BaseBackupWizardPage extends Page
{
    protected static ?string $cluster = Backups::class;

    protected static bool $shouldRegisterNavigation = false;

    public int $currentStep = 1;

    public array $records = [];

    public string|array|null $instanceIds;

    public string|array|null $clusterIds;

    public ?string $type = null;

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            ChooseClusterPage::getUrl() => __('Backups'),
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
                    'name' => __('Choose backup type'),
                    'url' => $this->getStepUrl(3),
                    'step' => 3,
                ],
                [
                    'name' => __('Create backups'),
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
                3 => ChooseBackupTypePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                    'updateType' => $this->type,
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
        } elseif ($this->currentStep > 2) {
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
            if (request('type') != null && BackupType::tryFrom(request('type')) != null) {
                $this->type = request('type');
            }
        } elseif ($this->currentStep > 3) {
            $this->type = request('type');
            if ($this->type === null || BackupType::tryFrom($this->type) === null) {
                $this->redirect(ChooseBackupTypePage::getUrl([
                    'clusterIds' => urlencode(serialize($this->clusterIds)),
                    'instanceIds' => urlencode(serialize($this->instanceIds)),
                ]));
            }
        }
    }
}
