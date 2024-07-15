<?php

namespace App\Filament\Admin\Pages;

use App\Enums\Status;
use App\Events\InstanceCreated;
use App\Filament\Admin\Resources\InstanceResource;
use App\Filament\Custom\Admin\Actions\Forms\CopyFieldStateAction;
use App\Models\Cluster;
use App\Models\Instance;
use App\Services\ModuleApiService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentView;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use JetBrains\PhpStorm\NoReturn;

use function Filament\Support\is_app_url;

class AddInstance extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';

    protected static string $view = 'filament.admin.pages.add-instance';

    protected static ?int $navigationSort = 1;

    public bool $connected = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill($this->data);
    }

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            '' => self::getTitle(),
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->model(Instance::class)
            ->statePath('data')
            ->schema([
                Forms\Components\Wizard::make()
                    ->schema([
                        Forms\Components\Wizard\Step::make('connection_details')
                            ->label(__('Connection details'))
                            ->columns(['sm' => 1, 'md' => 2, 'lg' => 2])
                            ->schema([
                                Forms\Components\TextInput::make('url')
                                    ->label(__('Base URL'))
                                    ->required()
                                    ->unique()
                                    ->url()
                                    ->live()
                                    ->suffix(ModuleApiService::PLUGIN_PATH)
                                    ->columnSpan('full')
                                    ->afterStateUpdated(fn () => $this->resetConnection()),
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
                            ])->afterValidation(function () {
                                if (! $this->connected) {
                                    $this->testConnection(true);
                                }
                            }),

                        Forms\Components\Wizard\Step::make('General information')
                            ->columns()
                            ->schema([
                                Grid::make()
                                    ->columnSpan(1)
                                    ->columns(1)
                                    ->schema([
                                        Forms\Components\TextInput::make('name')
                                            ->label(__('Name'))
                                            ->required(fn () => $this->connected),
                                        Forms\Components\TextInput::make('short_name')
                                            ->label(__('Shortened form'))
                                            ->live()
                                            ->required(fn () => $this->connected),
                                        Forms\Components\Select::make('cluster_id')
                                            ->label('Assign cluster')
                                            ->options(Cluster::all()->pluck('name', 'id'))
                                            ->searchable()
                                            ->required(fn () => $this->connected),
                                    ]),
                                Forms\Components\FileUpload::make('img_path')
                                    ->label(__('Choose image'))
                                    ->image()
                                    ->imageEditor()
                                    ->imageCropAspectRatio('1:1')
                                    ->disk(config('file-uploads.disk'))
                                    ->directory(config('file-uploads.'.Instance::class))
                                    ->rules(['nullable', 'mimes:jpg,jpeg,png', 'max:1024']),
                            ]),
                    ])
                    ->submitAction(new HtmlString(Blade::render(<<<'BLADE'
                        <x-filament::button
                            type="submit"
                            size="sm"
                        >
                            Submit
                        </x-filament::button>
                    BLADE))),

            ]);
    }

    private function resetConnection(): void
    {
        if ($this->connected) {
            $this->connected = false;
        }
    }

    #[NoReturn]
    public function testConnection($updateData = false): void
    {
        $data = $this->form->getState();
        try {
            // Get data from moodle site.
            $response = (new \App\Services\ModuleApiService)->getInstanceData($data['url'], $data['api_key']);
            if (! $response->ok()) {
                throw new \Exception('Connection to external moodle plugin failed! Status code: '.$response->status());
            }

            if ($updateData) {
                $moodleData = $response->collect();
                $data['name'] = $moodleData['site_fullname'];

                $this->form->fill($data);
            }

            // Change connected flag.
            $this->connected = true;
        } catch (\Exception $exception) {
            Log::error($exception->getMessage());
            Notification::make()
                ->title(__('Connection failed!'))
                ->danger()
                ->send();

            $this->halt();
        }
    }

    #[NoReturn]
    public function create(): void
    {
        $data = $this->form->getState();

        DB::beginTransaction();
        try {
            $response = (new \App\Services\ModuleApiService)->postApiKeyStatus($data['url'], $data['api_key'], dateToUnixOrNull($data['key_expiration_date']));
            if ($response->status() !== 201) {
                throw new \Exception('Creation failed due to unsuccessful api key status operation! Status code: '.$response->status());
            }

            $data['api_key'] = Crypt::encrypt($data['api_key']);
            $data['status'] = ($this->connected) ? Status::Connected->value : Status::Disconnected->value;
            $record = Instance::create($data);
            DB::commit();

            // Instance should be already created to commit create its plugins and update logs.
            // Best way to handle this data is to update via event!
            event(new InstanceCreated($record));
            $redirectUrl = $this->getRedirectUrl();
            Notification::make()
                ->success()
                ->title(__('Instance created'))
                ->body(__('Full sync still in progress (plugins, log, etc.).'))
                ->send();

            $this->redirect($redirectUrl, navigate: FilamentView::hasSpaMode() && is_app_url($redirectUrl));
        } catch (\Exception $error) {
            DB::rollBack();

            Log::error($error->getMessage());
            Notification::make()
                ->danger()
                ->title(__('Instance creation failed!'))
                ->send();
        }
    }

    public function getRedirectUrl(): string
    {
        return InstanceResource::getUrl();
    }
}
