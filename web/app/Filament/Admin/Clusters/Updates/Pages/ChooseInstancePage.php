<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Filament\Admin\Clusters\Updates;
use App\Filament\Admin\Resources\InstanceResource;
use App\Models\Instance;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class ChooseInstancePage extends Page
{
    protected static ?string $cluster = Updates::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.admin.pages.choose-instance-page';

    protected static ?string $title = 'Choose instances';

    public int $currentStep = 2;

    public array $records = [];

    public string|array|null $clusterIds;

    public bool $allRecordSelected = false;

    public string $search = '';

    public function mount(): void
    {
        $this->clusterIds = unserialize(urldecode(request('clusterIds')));
        $selectedInstances = unserialize(urldecode(request('instanceIds')));

        if (! is_array($this->clusterIds) || empty($this->clusterIds)) {
            $this->redirect(ChooseClusterPage::getUrl());
        }

        if (is_array($selectedInstances) && ! empty($selectedInstances)) {
            $this->records = $selectedInstances;
        }

        $this->areAllValuesSelected();
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
                    'url' => '#',
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

    public function getRecords(): \Illuminate\Database\Eloquent\Collection|array
    {
        return Instance::whereIn('cluster_id', $this->clusterIds)
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where('name', 'ilike', '%'.toLower($this->search).'%')
            )
            ->get();
    }

    public function selectRecord(int $recordId): void
    {
        if (! in_array($recordId, $this->records)) {
            $this->records[] = $recordId;
        } else {
            if (($key = array_search($recordId, $this->records)) !== false) {
                unset($this->records[$key]);
            }
        }

        $this->areAllValuesSelected();
    }

    public function isSelected(int $recordId): bool
    {
        return in_array($recordId, $this->records);
    }

    public function toggleAll(): void
    {
        if (! $this->allRecordSelected) {
            $this->records = $this->getRecords()->pluck('id')->toArray();
        } else {
            $this->records = [];
        }

        $this->areAllValuesSelected();
    }

    public function areAllValuesSelected(): bool
    {
        $areAllValuesSelected = count($this->getDiffBetweenPossibleAndSelectedRecords()) != 0;
        $this->allRecordSelected = ! (bool) $areAllValuesSelected;

        return $areAllValuesSelected;
    }

    public function getDiffBetweenPossibleAndSelectedRecords(): array
    {
        return array_diff($this->getAllPossibleIds(), $this->records);
    }

    public function getAllPossibleIds(): array
    {
        return $this->getRecords()->pluck('id')->toArray();
    }

    public function getToggleText(): string
    {
        return $this->allRecordSelected ? __('Deselect all') : __('Select all');
    }

    public function goToNextStep(): void
    {
        if (empty($this->records)) {
            Notification::make()
                ->danger()
                ->title(__('You should select at least one record.'))
                ->send();

            return;
        }

        $this->redirect(ChooseUpdateTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->records)),
        ]));
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseClusterPage::getUrl(['clusterIds' => urlencode(serialize($this->clusterIds))]));
    }
}
