<?php

namespace App\Filament\Admin\Clusters\Backups\Pages;

use App\Filament\Concerns\SearchableSelectCardsGridRecords;
use App\Models\Cluster;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class ChooseClusterPage extends BaseBackupWizardPage
{
    use SearchableSelectCardsGridRecords;

    protected static string $view = 'filament.admin.pages.choose-cluster-page';

    protected static ?string $title = 'Choose clusters';

    protected static ?string $slug = 'choose-clusters';

    public int $currentStep = 1;

    public array $records = [];

    public function mount(): void
    {
        parent::mount();

        $this->areAllValuesSelected();
    }

    public function getRecords(): Collection|array
    {
        return Cluster::with('instances')
            ->whereHas('instances')
            ->when($this->search !== '', fn (Builder $query) => $query->where('name', 'ilike', '%'.toLower($this->search).'%'))
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

        $this->redirect(ChooseInstancePage::getUrl(['clusterIds' => urlencode(serialize($this->records))]));
    }
}
