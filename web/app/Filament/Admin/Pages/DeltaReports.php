<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Resources\InstanceResource;
use App\Models\Cluster;
use App\Models\Instance;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
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
        dd('Implement when endpoint will be prepared!');

        // Todo: implement when endpoint will be provided!
        // $data = $this->form->getState();

        // $old = Storage::disk('local')->get('presets/razvoj-pf_20240530.xml');
        // $new = Storage::disk('local')->get('presets/test-01_ucilnice20240520.xml');
        // // one-line simply compare two strings
        // $this->diffHtml = DiffHelper::calculate($old, $new, config('delta-reports-settings.rendererName'), config('delta-reports-settings.differOptions'), config('delta-reports-settings.rendererOptions'));
    }
}
