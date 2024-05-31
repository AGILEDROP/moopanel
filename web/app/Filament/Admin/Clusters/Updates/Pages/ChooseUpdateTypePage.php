<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Enums\UpdateType;
use App\Models\Instance;
use App\Models\Plugin;
use App\Models\Update;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

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

    public function zipAction(): Action
    {
        // @todo: I need post endpoint for this!
        return Action::make('zip')
            ->label(__('Update with zip file'))
            ->icon('fas-upload')
            ->link()
            ->form([
                // todo: it would be best to update file to s3, and then just sent the id to the moodle instance!
                // other option is to store zip files to public storage and then send the public storage and delete it when it is finished!
                FileUpload::make('updates')
                    ->uploadingMessage(__('Uploading files...'))
                    ->multiple()
                    ->required()
                    ->disk('public')
                    ->directory('updates')
                    ->maxSize(1024)
                    ->rules('file|mimes:zip'),
            ])
            ->action(function (Action $action, array $data) {
                $updates = [];
                foreach ($data['updates'] as $key => $zipFile) {
                    $updates['updates'][] = Storage::disk('public')->url($zipFile);
                }

                //  $instances = Instance::whereIn('id', $this->instanceIds)->get();
                //  foreach ($instances as $instance) {
                //      // Trigger endpoint for each selected instance
                //      $instance->triggerZipFileUpdates($instance->url, Crypt::decrypt($instance->api_key), $updates);
                //      // Check if update was successful based on returned status and return user notification
                //      // which updates were successful/failed!
                //  }

                // Delete updated files!
                foreach ($data['updates'] as $key => $zipFile) {
                    Storage::disk('public')->delete($zipFile);
                }

                dd($updates);
            });
    }
}
