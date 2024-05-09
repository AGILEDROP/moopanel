<?php

namespace App\Filament\Admin\Clusters\Updates\Pages;

use App\Models\Plugin;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class PluginUpdatesPage extends BaseUpdateWizardPage implements HasTable
{
    use InteractsWithTable;

    protected static string $view = 'filament.admin.pages.plugin-updates-page';

    protected static ?string $title = 'Update plugins';

    public int $currentStep = 4;

    protected function getTableQuery()
    {
        return Plugin::whereHas('updates', function ($q) {
            $q->whereIn('updates.instance_id', $this->instanceIds);
        })->withExists('updates');
    }

    // todo: implement update action logic when update trigger endpoint will be provided (not yet)!
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

    public function goToPreviousStep(): void
    {
        $this->redirect(ChooseUpdateTypePage::getUrl([
            'clusterIds' => urlencode(serialize($this->clusterIds)),
            'instanceIds' => urlencode(serialize($this->instanceIds)),
            'updateType' => $this->updateType,
        ]));
    }
}
