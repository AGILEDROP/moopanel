<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Filament\Custom;
use App\Filament\Custom\Admin\Actions\Table\WizardPluginsUpdateAction;
use App\Filament\Custom\Admin\Actions\Table\WizardPluginsUpdateBulkAction;
use App\Models\Plugin;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PluginUpdatesPage extends BaseUpdateWizardPage implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.admin.pages.plugin-updates-page';

    protected static ?string $title = 'Update plugins';

    protected static ?string $slug = 'plugins';

    protected $listeners = ['pluginUpdatesPage' => '$refresh'];

    public int $currentStep = 4;

    protected function getTableQuery()
    {
        return Plugin::whereHas('updates', function ($q) {
            $q->whereIn('updates.instance_id', $this->instanceIds);
        })->withExists('updates');
    }

    // todo: show only stable releases?
    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->getTableQuery())
            ->columns([
                TextColumn::make('display_name')
                    ->label(__('Plugin name'))
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Model $record) => $record->type)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('component')
                    ->label(__('Component'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('updates_exists')
                    ->icon('fas-plug')
                    ->color(fn ($state) => $state === true ? 'danger' : 'gray')
                    ->label(__('Available updates'))
                    ->boolean(),
                TextColumn::make('newestAvailableUpdateVersion')
                    ->label(__('Newest version')),
                TextColumn::make('newestAvailableUpdateRelease')
                    ->label(__('Newest release')),
            ])
            ->defaultSort('display_name', 'desc')
            ->recordActions([
                WizardPluginsUpdateAction::make('update_plugins', $this->instanceIds, ['pluginUpdatesPage']),
                DeleteAction::make()
                    ->iconButton(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    WizardPluginsUpdateBulkAction::make('bulk_plugins_update', $this->instanceIds, ['pluginUpdatesPage']),
                    DeleteBulkAction::make(),
                ])->dropdownWidth(Width::Medium),
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
}
