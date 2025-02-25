<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Enums\UpdateType;
use App\Models\Plugin;
use App\Models\Update;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Notifications\Notification;

class ChooseUpdateTypePage extends BaseUpdateWizardPage implements HasActions
{
    use InteractsWithActions;

    protected static string $view = 'filament.admin.pages.choose-type-page';

    protected static ?string $title = 'Choose update type';

    protected static ?string $slug = 'choose-update-type';

    public int $currentStep = 3;

    public bool $hasHeaderAction = true;

    public function getTypes(): array
    {
        $types = [];
        foreach (UpdateType::cases() as $case) {
            $types[] = [
                'class' => 'h-[340px]',
                'type' => $case->value,
                'text' => $case->getText(),
                'icon' => $case->getIconComponent('h-32 w-32 mx-auto text-gray-500 dark:text-gray-300 mb-8'),
                'count' => $this->getTypeCount($case),
            ];
        }

        return $types;
    }

    public function selectType(?string $type): void
    {
        if ($type !== $this->type) {
            $this->type = $type;
        } else {
            $this->type = null;
        }
    }

    public function isSelected(?string $type): bool
    {
        return $type === $this->type;
    }

    public function goToNextStep(): void
    {
        if (! $this->validateSelectionBeforeNextStep()) {
            return;
        }

        $redirectPage = match ($this->type) {
            UpdateType::CORE_MINOR->value, UpdateType::CORE_MAJOR->value, UpdateType::CORE_MEGA->value => InstanceCoreUpdatesPage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
                'type' => $this->type,
            ]),
            UpdateType::PLUGIN->value => PluginUpdatesPage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
                'type' => $this->type,
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

    private function getTypeCount(UpdateType $updateTypeEnum)
    {
        return match ($updateTypeEnum) {
            UpdateType::PLUGIN => Plugin::whereHas('updates', function ($q) {
                $q->whereIn('updates.instance_id', $this->instanceIds);
            })->count(),
            UpdateType::CORE_MINOR => Update::whereIn('instance_id', $this->instanceIds)
                ->whereNull('plugin_id')
                ->where('type', UpdateType::CORE_MINOR)
                ->distinct('release')
                ->count(),
            UpdateType::CORE_MAJOR => Update::whereIn('instance_id', $this->instanceIds)
                ->whereNull('plugin_id')
                ->where('type', UpdateType::CORE_MAJOR)
                ->distinct('release')
                ->count(),
            UpdateType::CORE_MEGA => Update::whereIn('instance_id', $this->instanceIds)
                ->whereNull('plugin_id')
                ->where('type', UpdateType::CORE_MEGA)
                ->distinct('release')
                ->count(),
        };
    }

    private function validateSelectionBeforeNextStep(): bool
    {
        if ($this->type === null) {
            Notification::make()
                ->danger()
                ->title(__('You should select update type.'))
                ->send();

            return false;
        } elseif (UpdateType::tryFrom($this->type) === null) {
            Notification::make()
                ->danger()
                ->title(__('Selected update type is not allowed!'))
                ->send();

            return false;
        } elseif ($this->getTypeCount(UpdateType::tryFrom($this->type)) === 0) {
            Notification::make()
                ->info()
                ->title(__('There are no new updates for selected update type.'))
                ->send();

            return false;
        }

        return true;
    }

    public function redirectToZipUpdatesAction(): Action
    {
        return Action::make('zip')
            ->label(__('Update with zip file'))
            ->icon('fas-upload')
            ->link()
            ->url(ZipUpdatesPage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
                'type' => 'zip',
            ]));
    }
}
