<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Jobs\ModuleApi\Sync;
use App\Models\Instance;
use App\Services\ModuleApiService;
use App\UseCases\Syncs\SingleInstance\PluginsSyncType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Illuminate\Support\Facades\Crypt;
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
                    ->maxSize(1024)
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
        // TODO: use combination of trigger and post request to avoid timeouts!
        $this->zipResults = [];
        $moduleApi = new ModuleApiService();
        $publicStorageUrl = config('filesystems.disks.public.url').'/';

        // Validate form and set updates for post request!
        $data = $this->form->getState();
        $updates = [];
        foreach ($data['files'] as $key => $zipFile) {
            $updates[] = Storage::disk('public')->url($zipFile);
        }

        // Run updates and display results!
        $instances = Instance::whereIn('id', $this->instanceIds)->get();
        foreach ($instances as $instance) {
            // Trigger updates with post request!
            $response = $moduleApi->triggerPluginZipFileUpdates($instance->url, Crypt::decrypt($instance->api_key), $updates);
            if (! $response->ok()) {
                Log::error('Zip updates failed with '.$response->status().' response status on instance '.$instance->name.'!');
                $this->zipResults[$instance->name]['results'] = null;
                $this->zipResults[$instance->name]['error'] = __('Zip files update failed, due to connection error. Please contact the administrator.');

                continue;
            }

            // Set update results.
            foreach ($response->json('updates') as $key => $values) {
                $originalFilename = $data['original_filenames'][str_replace($publicStorageUrl, '', $key)];
                $this->zipResults[$instance->name]['results'][$originalFilename] = $values;

                // Sync data if at least one update is successful.
                if (isset($values['status']) && $values['status'] === true) {
                    new Sync($instance, PluginsSyncType::TYPE, 'Plugin sync failed!');
                }
            }
        }

        // Delete updated files!
        foreach ($data['files'] as $key => $zipFile) {
            Storage::disk('public')->delete($zipFile);
        }

        // Needed to reset files inside the FileUpload component.
        $this->form->fill([]);
    }
}
