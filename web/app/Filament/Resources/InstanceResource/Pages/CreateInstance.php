<?php

namespace App\Filament\Resources\InstanceResource\Pages;

use App\Enums\Status;
use App\Filament\Custom\Actions\CopyFieldStateAction;
use App\Filament\Resources\InstanceResource;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Exceptions\Halt;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CreateInstance extends CreateRecord
{
    protected static string $resource = InstanceResource::class;

    public bool $connected = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->heading(__('Connection details'))
                    ->icon('fas-network-wired')
                    ->iconSize('sm')
                    ->schema([
                        Forms\Components\Grid::make()->schema([
                            Forms\Components\TextInput::make('url')
                                ->label(__('Base URL'))
                                ->required()
                                ->unique()
                                ->url()
                                ->live()
                                ->afterStateUpdated(fn () => $this->resetConnection()),
                        ])->columns(),
                        Forms\Components\Grid::make()->schema([
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
                                            $this->resetConnection();
                                        }),
                                ])
                                ->helperText(__('The API Key must include characters from a minimum of three out of the
                        following five categories: uppercase letters, lowercase letters, digits, non-alphanumeric
                        characters, and Unicode characters.'))
                                ->required()
                                ->minValue(40)
                                ->regex("/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/")
                                ->live()
                                ->afterStateUpdated(fn () => $this->resetConnection()),
                            Forms\Components\DatePicker::make('key_expiration_date')
                                ->label(__('Expiration date'))
                                ->native(false)
                                ->displayFormat('d/m/Y')
                                ->suffixAction(
                                    Forms\Components\Actions\Action::make('Expiration date')
                                        ->label(__('Clear expiration date'))
                                        ->icon('heroicon-o-x-mark')
                                        ->action(function (Forms\Set $set) {
                                            $set('key_expiration_date', null);
                                            $this->resetConnection();
                                        }),
                                )
                                ->helperText(__('If this field is empty, the API key will be considered valid indefinitely.'))
                                ->minDate(now())
                                ->maxDate(now()->addYear())
                                ->live()
                                ->afterStateUpdated(fn () => $this->resetConnection()),
                        ])->columns(),
                    ]),

                Forms\Components\Tabs::make('instance_data')
                    ->live()
                    ->hidden(fn () => ! $this->connected)
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('site_settings')
                            ->label(__('Site settings'))
                            ->schema([
                                Forms\Components\TextInput::make('site_name')
                                    ->label(__('Site name'))
                                    ->live()
                                    ->required(fn () => $this->connected)
                                    ->readOnly()
                                    ->afterStateUpdated(fn () => $this->resetConnection()),
                                Forms\Components\TextInput::make('logo')
                                    ->label(__('Logo'))
                                    ->live()
                                    ->url()
                                    ->required(fn () => $this->connected)
                                    ->readOnly()
                                    ->afterStateUpdated(fn () => $this->resetConnection()),
                                Forms\Components\TextInput::make('theme')
                                    ->label(__('Theme'))
                                    ->live()
                                    ->required(fn () => $this->connected)
                                    ->readOnly()
                                    ->afterStateUpdated(fn () => $this->resetConnection()),
                                Forms\Components\TextInput::make('version')
                                    ->label(__('Version'))
                                    ->live()
                                    ->required(fn () => $this->connected)
                                    ->readOnly()
                                    ->afterStateUpdated(fn () => $this->resetConnection()),
                            ])
                            ->columns(),
                        Forms\Components\Tabs\Tab::make('Tags')
                            ->label(__('Tags'))
                            ->schema([
                                Forms\Components\Select::make('tags')
                                    ->relationship('tags', 'name')
                                    ->multiple()
                                    ->createOptionForm([
                                        Forms\Components\TextInput::make('name')
                                            ->required(),
                                    ]),
                            ])->columns(),
                    ])->columnSpanFull(),
            ])->columns();
    }

    protected function getFormActions(): array
    {
        return [
            $this->getTestConnectionAction(),
            $this->getCreateFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getSubmitFormAction(): Action
    {
        return ($this->connected) ? $this->getCreateFormAction() : $this->getTestConnectionAction();
    }

    protected function getCreateFormAction(): Action
    {
        return Action::make('create')
            ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
            ->submit('create')
            ->keyBindings(['mod+s'])
            ->hidden(fn () => ! $this->connected);
    }

    protected function getTestConnectionAction(): Action
    {
        return Action::make('testConnection')
            ->label(__('Test Connection'))
            ->action(fn () => $this->testConnection())
            ->hidden(fn () => $this->connected);
    }

    public function testConnection(): void
    {
        // Validate and get form data.
        $data = $this->form->getState();

        try {
            // Get data from moodle site.
            $response = (new \App\Services\ModuleApiService)->getInstanceData($data['url'], $data['api_key']);
            if (! $response->ok()) {
                throw new \Exception('Connection to external moodle plugin failed!');
            }

            // Set fetched data.
            $moodleData = $response->collect();
            $data['site_name'] = $moodleData['site_fullname'];
            $data['logo'] = $moodleData['logo'];
            $data['theme'] = $moodleData['theme'];
            $data['version'] = $moodleData['moodle_version'];
            $this->form->fill($data);

            // Change connected flag.
            $this->connected = true;

            Notification::make()
                ->title(__('Connection established.'))
                ->success()
                ->send();
        } catch (\Exception $exception) {
            Log::error($exception);

            Notification::make()
                ->title(__('Connection failed!'))
                ->danger()
                ->send();
        }
    }

    private function resetConnection(): void
    {
        if ($this->connected) {
            $this->connected = false;
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['api_key'] = Crypt::encrypt($data['api_key']);
        $data['status'] = Status::Connected->value;

        return $data;
    }

    /**
     * @throws Halt
     */
    protected function handleRecordCreation(array $data): Model
    {
        $response = (new \App\Services\ModuleApiService)->postApiKeyStatus($data['url'], Crypt::decrypt($data['api_key']), dateToUnixOrNull($data['key_expiration_date']));
        if ($response->status() !== 201) {
            Log::error('Creation failed due to unsuccessful api key status operation!');

            Notification::make()
                ->danger()
                ->title(__('Instance creation failed!'))
                ->body(__('The Moodle instance values cannot be updated at the moment. Please try again later.'))
                ->persistent()
                ->send();

            $this->halt();
        }

        return parent::handleRecordCreation($data);
    }
}
