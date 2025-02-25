<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Filament\Concerns\SearchableSelectCardsGridRecords;
use App\Models\Instance;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ChooseInstancePage extends BaseUpdateWizardPage
{
    use SearchableSelectCardsGridRecords;

    protected static string $view = 'filament.admin.pages.choose-instance-page';

    protected static ?string $title = 'Choose instances';

    protected static ?string $slug = 'choose-instances';

    public int $currentStep = 2;

    public array $records = [];

    public function mount(): void
    {
        parent::mount();

        $this->areAllValuesSelected();
    }

    public function getRecords(): Collection|array
    {
        return Instance::whereIn('cluster_id', $this->clusterIds)
            ->when($this->search !== '', fn (Builder $query) => $query
                ->where('name', 'ilike', '%'.toLower($this->search).'%')
            )
            ->get();
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
