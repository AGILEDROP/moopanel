<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Enums\UpdateType;
use App\Models\Plugin;
use App\Models\Update;
use Filament\Notifications\Notification;

class ChooseUpdateTypePage extends BaseUpdateWizardPage
{
    protected static string $view = 'filament.admin.pages.choose-update-type-page';

    protected static ?string $title = 'Choose update type';

    public int $currentStep = 3;

    public function getUpdateTypes(): array
    {
        $updateTypes = [];
        foreach (UpdateType::cases() as $case) {
            $updateTypes[] = [
                'type' => $case->value,
                'text' => $case->getText(),
                'count' => $this->getUpdateTypeCount($case),
            ];
        }

        return $updateTypes;
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
        if (! $this->validateSelectionBeforeNextStep()) {
            return;
        }

        //@todo: update based on the diff between minor and major core update!
        $redirectPage = match ($this->updateType) {
            UpdateType::MINOR_CORE->value, UpdateType::MAJOR_CORE->value => InstanceCoreUpdatesPage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
                'updateType' => $this->updateType,
            ]),
            UpdateType::PLUGIN->value => PluginUpdatesPage::getUrl([
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

    private function getUpdateTypeCount(UpdateType $updateTypeEnum)
    {
        return match ($updateTypeEnum) {
            UpdateType::PLUGIN => Plugin::whereHas('updates', function ($q) {
                $q->whereIn('updates.instance_id', $this->instanceIds);
            })->count(),
            UpdateType::MINOR_CORE, UpdateType::MAJOR_CORE => Update::whereIn('instance_id', $this->instanceIds)->whereNull('plugin_id')->distinct('release')->count(),
        };
    }

    private function validateSelectionBeforeNextStep(): bool
    {
        if ($this->updateType === null) {
            Notification::make()
                ->danger()
                ->title(__('You should select update type.'))
                ->send();

            return false;
        } elseif (UpdateType::tryFrom($this->updateType) === null) {
            Notification::make()
                ->danger()
                ->title(__('Selected update type is not allowed!'))
                ->send();

            return false;
        } elseif ($this->getUpdateTypeCount(UpdateType::tryFrom($this->updateType)) === 0) {
            Notification::make()
                ->info()
                ->title(__('There are no new updates for selected update type.'))
                ->send();

            return false;
        }

        return true;
    }
}