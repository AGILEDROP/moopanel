<?php

namespace App\Filament\Resources\InstanceResource\Pages;

use App\Filament\Custom\Actions\CopyFieldStateAction;
use App\Filament\Resources\InstanceResource;
use App\Models\Instance;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EditInstance extends EditRecord
{
    protected static string $resource = InstanceResource::class;

    protected static ?string $title = 'Edit Moodle Instance';

    protected static string $view = 'filament.resources.instance-resource.pages.edit-instance';

    public ?array $connectionData = [];

    public ?array $instanceData = [];

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make()
                ->icon('heroicon-o-eye'),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash'),
        ];
    }

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->fillForms();

        $this->previousUrl = url()->previous();
    }

    protected function fillForms(): void
    {
        $data = $this->getRecord()->attributesToArray();
        $data['use_existing_api_key'] = true;

        $this->settingsForm->fill($data);
        $this->connectionSettingsForm->fill($data);
    }

    protected function getForms(): array
    {
        return [
            'connectionSettingsForm',
            'settingsForm',
        ];
    }

    public function connectionSettingsForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('url')
                                ->label(__('Base URL'))
                                ->required()
                                ->unique(ignorable: $this->record)
                                ->url(),
                        ])->columns(),

                        Forms\Components\Grid::make()->schema([
                            Forms\Components\Toggle::make('use_existing_api_key')
                                ->label(__('Use existing API Key'))
                                ->live(),
                        ])->columns(),

                        Forms\Components\Grid::make()
                            ->live()
                            ->disabled(fn (Forms\Get $get): bool => $get('use_existing_api_key'))
                            ->hidden(fn (Forms\Get $get): bool => $get('use_existing_api_key'))
                            ->schema([
                                Forms\Components\TextInput::make('api_key')
                                    ->label(__('API Key'))
                                    ->password()
                                    ->revealable()
                                    ->hintAction(fn () => CopyFieldStateAction::make('copy_api_key', 'Copy API Key'))
                                    ->prefixActions([
                                        Forms\Components\Actions\Action::make('generateApiKey')
                                            ->label(__('Generate key'))
                                            ->icon('heroicon-o-arrow-path')
                                            ->action(function (Forms\Set $set) {
                                                $set('api_key', Str::password(60));
                                            }),
                                    ])
                                    ->helperText(__('The API Key must include characters from a minimum of three out of the
                                following five categories: uppercase letters, lowercase letters, digits, non-alphanumeric
                                characters, and Unicode characters.'))
                                    ->required()
                                    ->minValue(40)
                                    ->regex("/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/")
                                    ->live(),
                                Forms\Components\DatePicker::make('key_expiration_date')
                                    ->label(__('Expiration date'))
                                    ->native(false)
                                    ->displayFormat('d/m/Y')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('clear_expiration_date')
                                            ->label(__('Clear expiration date'))
                                            ->icon('heroicon-o-x-mark')
                                            ->action(function (Forms\Set $set) {
                                                $set('key_expiration_date', null);
                                            }),
                                    )
                                    ->helperText(__('If this field is empty, the API key will be considered valid indefinitely.'))
                                    ->minDate(now())
                                    ->maxDate(now()->addYear()),
                            ])->columns(),
                    ]),
            ])
            ->model($this->getModel())
            ->statePath('connectionData');
    }

    protected function getConnectionSettingsFormActions(): array
    {
        return [
            Action::make('updateConnectionSettings')
                ->label(__('Reconnect'))
                ->action('startReconnecting'),
        ];
    }

    public function startReconnecting(): void
    {
        $this->dispatch('open-modal', id: 'confirm-connection-changes');
    }

    public function updateConnectionSettings(): void
    {
        // Validate and get form data.
        $data = $this->connectionSettingsForm->getState();

        DB::beginTransaction();
        try {
            // Get API Key data.
            $apiKey = $data['use_existing_api_key'] ? Crypt::decrypt($this->record->api_key) : $data['api_key'];

            // Get data from moodle site.
            $response = (new \App\Services\ModuleApiService)->getInstanceData($data['url'], $apiKey);
            if (! $response->ok()) {
                throw new \Exception('Connection to external moodle plugin failed!');
            }

            // Update settings data with fetched data from the endpoint.
            $moodleData = $response->collect();
            $settingsData = $this->connectionSettingsForm->getState();
            $settingsData['site_name'] = $moodleData['site_fullname'];
            $settingsData['logo'] = $moodleData['logo'];
            $settingsData['theme'] = $moodleData['theme'];
            $settingsData['version'] = $moodleData['moodle_version'];
            $this->settingsForm->fill($settingsData);

            // Update connection
            $instance = Instance::findOrFail($this->record->id);
            if (! $data['use_existing_api_key']) {
                $data['api_key'] = Crypt::encrypt($apiKey);
            }
            $instance->update($data);
            $instance = $instance->refresh();

            // Send new expiration date to moodle instance.
            $response = (new \App\Services\ModuleApiService)->postApiKeyStatus($instance->url, Crypt::decrypt($instance->api_key), dateToUnixOrNull($instance->key_expiration_date));
            if ($response->status() !== 201) {
                throw new \Exception('Creation failed due to unsuccessful api key status operation!');
            }

            // Commit changes.
            DB::commit();

            $this->closeModal('confirm-connection-changes');
            Notification::make()
                ->title(__('Connection updated.'))
                ->success()
                ->send();
        } catch (\Exception $exception) {
            // Rollback changes.
            DB::rollBack();
            Log::error($exception);
            Notification::make()
                ->title(__('Connection update failed!'))
                ->danger()
                ->send();
        }
    }

    public function closeModal(string $id): void
    {
        $this->dispatch('close-modal', id: $id);
    }

    public function SettingsForm(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('settings')
                    ->label(__('Settings'))
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('General settings')
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label(__('Site name'))
                                    ->required(),
                                Forms\Components\TextInput::make('logo')
                                    ->label(__('Logo'))
                                    ->url(),
                                Forms\Components\TextInput::make('theme')
                                    ->label(__('Theme')),
                                Forms\Components\TextInput::make('version')
                                    ->label(__('Version')),
                            ])->columns(),
                    ]),
            ])
            ->model($this->getModel())
            ->statePath('instanceData')
            ->disabled();
    }

    protected function getSettingsFormActions(): array
    {
        return [
            // Action::make('updateSettings')
            //     ->label(__('filament-panels::pages/auth/edit-profile.form.actions.save.label'))
            //     ->submit('settingsForm'),
        ];
    }

    public function updateSettings(): void
    {
        //
    }
}
