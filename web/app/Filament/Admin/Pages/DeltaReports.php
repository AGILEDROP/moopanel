<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\InstanceResource;
use App\Models\Cluster;
use App\Models\DeltaReport;
use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use App\Services\ModuleApiService;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use JetBrains\PhpStorm\NoReturn;
use Jfcherng\Diff\DiffHelper;

class DeltaReports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string $view = 'filament.admin.pages.delta-reports';

    protected static ?int $navigationSort = 4;

    public ?array $data = [];

    public ?string $diffHtml = null;

    public function mount(): void
    {
        if (! empty(request()->query('delta_report_id', ''))) {
            $deltaReportId = request()->query('delta_report_id', '');
            $this->renderDiffComparison($deltaReportId);
        }

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
            ->statePath('data')
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'sm' => 2,
                ])->schema([
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('first_cluster_id')
                                ->label(__('Cluster'))
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('first_instance_id', null))
                                ->options(fn () => Cluster::pluck('name', 'id'))
                                ->rules(['required', 'integer', 'exists:clusters,id']),
                            Forms\Components\Select::make('first_instance_id')
                                ->label(__('Instance'))
                                ->disabled(fn (Forms\Get $get) => $get('first_cluster_id') === null)
                                ->options(fn (Forms\Get $get) => $this->getOptions($get('first_cluster_id')))
                                ->rules(['required', 'integer', 'exists:instances,id']),
                        ])->columnSpan(1),
                    Forms\Components\Section::make()
                        ->schema([
                            Forms\Components\Select::make('second_cluster_id')
                                ->label(__('Cluster'))
                                ->live()
                                ->afterStateUpdated(fn (Forms\Set $set) => $set('second_instance_id', null))
                                ->options(fn () => Cluster::pluck('name', 'id'))
                                ->rules(['required', 'integer', 'exists:clusters,id']),
                            Forms\Components\Select::make('second_instance_id')
                                ->label(__('Instance'))
                                ->live()
                                ->disabled(fn (Forms\Get $get) => $get('second_cluster_id') === null)
                                ->options(fn (Forms\Get $get) => $this->getOptions($get('second_cluster_id')))
                                ->rules(['required', 'integer', 'exists:instances,id', 'different:data.first_instance_id'])
                                ->validationMessages([
                                    'different' => __('You can\'t compare same instance.'),
                                ]),
                        ])->columnSpan(1),
                ]),
            ]);
    }

    private function getOptions(?int $clusterId)
    {
        $options = Instance::where('cluster_id', $clusterId)->pluck('name', 'id');
        if ($cluster = Cluster::find($clusterId)) {
            $masterId = $cluster->master_id;

            if ($cluster->master()->exists()) {
                $options->map(function ($name, $id) use ($masterId, $options) {
                    if ($id === $masterId) {
                        $options[$id] = __('MASTER').': '.$name;
                    }
                });
            }
        }

        return $options;
    }

    public function getFormActions(): array
    {
        return [
            Action::make('compare')
                ->label(__('Compare'))
                ->icon('fas-arrow-right-arrow-left')
                ->iconSize('sm')
                ->size('lg')
                ->extraAttributes(['class' => 'ms-auto'])
                ->action('compare'),
        ];
    }

    #[NoReturn]
    public function compare(): void
    {
        $data = $this->form->getState();

        $firstInstance = Instance::withoutGlobalScope(InstanceScope::class)->find($data['first_instance_id']);
        $secondInstance = Instance::withoutGlobalScope(InstanceScope::class)->find($data['second_instance_id']);

        // Request admin preset for the instances if they dont have any pending delta report generation
        $firstInstanceHasPendingDeltaReportGenerationProcess = $this->hasPendingDeltaReportGenerationProcess($firstInstance);
        if (! $firstInstanceHasPendingDeltaReportGenerationProcess) {
            $isFirstRequestSuccessfullySent = $this->requestAdminPreset($firstInstance);

            if (! $isFirstRequestSuccessfullySent) {
                return;
            }
        }

        // Request admin preset for the instances if they dont have any pending delta report generation
        $secondInstanceHasPendingDeltaReportGenerationProcess = $this->hasPendingDeltaReportGenerationProcess($secondInstance);
        if (! $secondInstanceHasPendingDeltaReportGenerationProcess) {
            $isSecondRequestSuccessfullySent = $this->requestAdminPreset($secondInstance);

            if (! $isSecondRequestSuccessfullySent) {
                return;
            }
        }

        // Skip creating new delta report if there is already a pending delta report generation for selected two instances
        if ($firstInstanceHasPendingDeltaReportGenerationProcess && $secondInstanceHasPendingDeltaReportGenerationProcess) {
            Notification::make()
                ->warning()
                ->title(__('Delta report generation already in progress.'))
                ->body(__('There is already a delta report generation in progress for those instances. Please wait. You will be notified when it is ready.'))
                ->icon('heroicon-o-document-text')
                ->iconColor('warning')
                ->persistent()
                ->send();

            return;
        }

        DeltaReport::create([
            'name' => $firstInstance->name.' vs '.$secondInstance->name,
            'user_id' => auth()->id(),
            'first_instance_id' => $data['first_instance_id'],
            'second_instance_id' => $data['second_instance_id'],
        ]);

        Notification::make()
            ->success()
            ->title(__('Delta report creation in progress.'))
            ->body(__('It might take a while to generate delta report. Please wait. You will be notified when it is ready.'))
            ->icon('heroicon-o-document-text')
            ->iconColor('success')
            ->seconds(10)
            ->send();
    }

    /**
     * Request admin preset for the instance.
     */
    private function requestAdminPreset(Instance $instance): bool
    {
        $response = Http::withHeaders([
            'X-API-KEY' => Crypt::decrypt($instance->api_key),
        ])
            ->get($instance->url.ModuleApiService::PLUGIN_PATH.'/admin_presets', [
                'instanceid' => $instance->id,
            ]);

        if (! $response->successful()) {
            Notification::make()
                ->danger()
                ->title(__('Failed to request admin preset.'))
                ->body(__('Failed to request admin preset for instance :instance.', ['instance' => $instance->name]))
                ->icon('heroicon-o-document-text')
                ->iconColor('danger')
                ->persistent()
                ->send();

            Log::error('Failed to request admin preset for instance '.$instance->name.'. Response: '.$response->body());

            return false;
        }

        return true;
    }

    /**
     * Check if there is a pending delta report generation for the instance.
     */
    private function hasPendingDeltaReportGenerationProcess(Instance $instance): bool
    {
        return DeltaReport::where(function ($query) use ($instance) {
            $query->where('first_instance_id', $instance->id)
                ->where('first_instance_config_received', false)
                ->where('user_id', auth()->id());
        })->orWhere(function ($query) use ($instance) {
            $query->where('second_instance_id', $instance->id)
                ->where('second_instance_config_received', false)
                ->where('user_id', auth()->id());
        })->exists();
    }

    /**
     * Render diff comparison between two instances configurations.
     */
    private function renderDiffComparison(string $deltaReportId): void
    {
        if (! DeltaReport::where('id', (int) $deltaReportId)->exists()) {
            return;
        }

        $deltaReport = DeltaReport::find($deltaReportId);
        $firstInstance = Instance::withoutGlobalScope(InstanceScope::class)->find($deltaReport->first_instance_id);
        $secondInstance = Instance::withoutGlobalScope(InstanceScope::class)->find($deltaReport->second_instance_id);

        $old = Storage::disk('local')->get($firstInstance->configuration_path);
        $new = Storage::disk('local')->get($secondInstance->configuration_path);

        $this->data = [
            'first_cluster_id' => $firstInstance->cluster_id,
            'first_instance_id' => $firstInstance->id,
            'second_cluster_id' => $secondInstance->cluster_id,
            'second_instance_id' => $secondInstance->id,
        ];

        $this->diffHtml = DiffHelper::calculate($old, $new, config('delta-reports-settings.rendererName'), config('delta-reports-settings.differOptions'), config('delta-reports-settings.rendererOptions'));
    }
}
