<?php

namespace App\Filament\Admin\Clusters\Backups\Pages;

use App\Enums\BackupType;
use Filament\Notifications\Notification;

class ChooseBackupTypePage extends BaseBackupWizardPage
{
    protected static string $view = 'filament.admin.pages.choose-type-page';

    protected static ?string $title = 'Choose backup type';

    protected static ?string $slug = 'choose-backup-type';

    public int $currentStep = 3;

    public bool $hasHeaderAction = false;

    public function getTypes(): array
    {
        $types = [];
        foreach (BackupType::cases() as $case) {
            $types[] = [
                'class' => 'xl:w-[340px]',
                'type' => $case->value,
                'text' => $case->getText(),
                'icon' => $case->getIconComponent('h-32 w-32 mx-auto text-gray-500 dark:text-gray-300 mb-8'),
                'count' => false,
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

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseInstancePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
        ]));
    }

    public function goToNextStep(): void
    {
        if (! $this->validateSelectionBeforeNextStep()) {
            return;
        }

        // todo: implement next page based on selection.
        dd('Implement next page based on selection.');
    }

    private function validateSelectionBeforeNextStep(): bool
    {
        if ($this->type === null) {
            Notification::make()
                ->danger()
                ->title(__('You should select backup type.'))
                ->send();

            return false;
        } elseif (BackupType::tryFrom($this->type) === null) {
            Notification::make()
                ->danger()
                ->title(__('Selected backup type is not allowed!'))
                ->send();

            return false;
        }

        return true;
    }
}
