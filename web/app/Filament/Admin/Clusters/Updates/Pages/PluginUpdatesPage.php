<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Filament\Admin\Clusters\Updates;
use App\Filament\Admin\Resources\InstanceResource;
use App\Models\Plugin;
use Filament\Pages\Page;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class PluginUpdatesPage extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $cluster = Updates::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $view = 'filament.admin.pages.plugin-updates-page';

    protected static ?string $title = 'Update plugins';

    public int $currentStep = 4;

    public string|array|null $instanceIds;

    public string|array|null $clusterIds;

    public bool $hasUpdateAllAction = false;

    public ?string $updateType = null;

    public function mount(): void
    {
        $this->clusterIds = unserialize(urldecode(request('clusterIds')));
        $this->instanceIds = unserialize(urldecode(request('instanceIds')));
        $this->updateType = request('updateType');

        if (! is_array($this->clusterIds) || empty($this->clusterIds)) {
            $this->redirect(ChooseClusterPage::getUrl());
        }
        if (! is_array($this->instanceIds) || empty($this->instanceIds)) {
            $this->redirect(ChooseInstancePage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
            ]));
        }
        // @todo: check if update type is in enum else return back to update page!
        if ($this->updateType === null) {
            $this->redirect(ChooseUpdateTypePage::getUrl([
                'clusterIds' => urlencode(serialize($this->clusterIds)),
                'instanceIds' => urlencode(serialize($this->instanceIds)),
            ]));
        }
    }

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            ChooseClusterPage::getUrl() => __('Updates'),
        ];
    }

    protected function getTableQuery()
    {
        return Plugin::whereHas('updates', function ($q) {
            $q->whereIn('updates.instance_id', $this->instanceIds);
        })->withExists('updates');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label(__('Plugin name'))
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Model $record) => $record->type)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->hidden()
                    ->sortable()
                    ->badge(),
                Tables\Columns\IconColumn::make('updates_exists')
                    ->icon('fas-plug')
                    ->color(fn ($state) => $state === true ? 'danger' : 'gray')
                    ->label(__('Available updates'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('newestAvailableUpdateVersion')
                    ->label(__('Newest version')),
                Tables\Columns\TextColumn::make('newestAvailableUpdateRelease')
                    ->label(__('Newest release')),

            ])
            ->defaultSort('display_name', 'desc')
            ->actions([
                Action::make('update_plugin')
                    ->label(__('Update'))
                    ->iconButton()
                    ->icon('heroicon-o-arrow-up-circle')
                    ->action(fn () => dd('Implement action logic')),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('update_plugins')
                        ->label(__('Update'))
                        ->icon('heroicon-o-arrow-up-circle')
                        ->action(fn () => dd('Implement action logic!')),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function getTableWizardHeaderData(): ?array
    {
        return [
            'steps' => [
                [
                    'name' => 'Choose cluster',
                    'url' => ChooseClusterPage::getUrl([
                        'clusterIds' => urlencode(serialize($this->clusterIds)),
                    ]),
                    'step' => 1,
                ],
                [
                    'name' => 'Choose instances',
                    'url' => ChooseInstancePage::getUrl([
                        'clusterIds' => urlencode(serialize($this->clusterIds)),
                        'instanceIds' => urlencode(serialize($this->instanceIds)),
                    ]),
                    'step' => 2,
                ],
                [
                    'name' => 'Choose update type',
                    'url' => ChooseUpdateTypePage::getUrl([
                        'clusterIds' => urlencode(serialize($this->clusterIds)),
                        'instanceIds' => urlencode(serialize($this->instanceIds)),
                        'updateType' => $this->updateType,
                    ]),
                    'step' => 3,
                ],
                [
                    'name' => 'Update',
                    'url' => '#',
                    'step' => 4,
                ],
            ],
        ];
    }

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseUpdateTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
            'updateType' => $this->updateType,
        ]));
    }
}
