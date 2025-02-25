<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Jobs\Update\PluginZipUpdateJob;
use App\Models\Instance;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ZipUpdatesPage extends BaseUpdateWizardPage implements HasForms
{
    use InteractsWithForms;

    protected static string $view = 'filament.admin.pages.zip-updates-page';

    protected static ?string $title = 'Zip Updates';

    protected static ?string $slug = 'zip';

    public int $currentStep = 4;

    public bool $hasUpdateAllAction = true;

    public ?array $zipResults = [];

    public ?array $data = [];

    public function mount(): void
    {
        parent::mount();
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                FileUpload::make('files')
                    ->label(__('Zip files'))
                    ->uploadingMessage(__('Uploading files...'))
                    ->multiple()
                    ->required()
                    ->disk('public')
                    ->directory('zip_updates')
                    ->maxSize(10240) // 10MB
                    ->storeFileNamesIn('original_filenames')
                    ->rules('file|mimes:zip')
                    ->helperText(__('Upload zip files you want to use for updates on chosen instances.'))
                    ->afterStateUpdated(fn () => $this->zipResults = []),
            ]);
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseUpdateTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
            'type' => $this->type,
        ]));
    }

    public function updateAll(): void
    {
        $data = $this->form->getState();

        $updates = [];
        // For saving zip file name to be able to write it to the update request items table
        $updatesAdittionalInfo = [];

        foreach ($data['files'] as $key => $zipFile) {
            $fullFilePath = Storage::disk('public')->url($zipFile);

            $updates[] = $fullFilePath;

            // Get original filename for zip file
            try {
                $updatesAdittionalInfo[] = [
                    'zip_name' => $data['original_filenames'][$zipFile],
                    'zip_path' => $fullFilePath,
                ];
            } catch (\Exception $e) {
                Log::error('Failed to get original filename for zip file: '.$zipFile.' Error message: '.$e->getMessage());

                $updatesAdittionalInfo[] = [
                    'zip_name' => $zipFile,
                    'zip_path' => $fullFilePath,
                ];
            }
        }

        $instances = Instance::whereIn('id', $this->instanceIds)->get();
        foreach ($instances as $instance) {

            $payload = [
                'user_id' => auth()->id(),
                'username' => (auth()->user()->email) ?? (auth()->user()->username ?? 'unknown'),
                'instance_id' => $instance->id,
                'updates' => $updates,
                'temp_updates_data' => $updatesAdittionalInfo,
            ];

            PluginZipUpdateJob::dispatch($instance, auth()->user(), $payload);
        }

        if (count($instances) && count($updates)) {
            Notification::make()
                ->success()
                ->title(__('Zip updates have been successfully triggered.'))
                ->body(__('Zip updates(:count) for instances(:instances) have been successfully triggered.', ['count' => count($updates), 'instances' => count($instances)]))
                ->icon('heroicon-o-arrow-up-circle')
                ->iconColor('success')
                ->seconds(7)
                ->send();
        }

        // Needed to reset files inside the FileUpload component.
        $this->form->fill([]);
    }
}
