<?php

namespace App\Livewire\App\Core;

use App\Enums\UpdateMaturity;
use App\Enums\UpdateType;
use App\Filament\Custom\App as CustomAppComponents;
use App\Models\Instance;
use App\Models\Update;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AvailableUpdatesTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected $listeners = ['availableCoreUpdatesTableComponent' => '$refresh'];

    public function table(Table $table): Table
    {
        $syncType = SyncTypeFactory::create(CoreSyncType::TYPE, Instance::find(filament()->getTenant()->id));

        return $table
            ->heading(__('Available Updates'))
            ->description($syncType->getLatestTimeText())
            ->query(Update::query()->whereNull('plugin_id'))
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (UpdateType $state): string => $state->toReadableString())
                    ->icon(fn (UpdateType $state): string => $state->getIcon())
                    ->sortable(),
                Tables\Columns\TextColumn::make('release')
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->sortable(),
                Tables\Columns\TextColumn::make('maturity')
                    ->badge()
                    ->formatStateUsing(fn (UpdateMaturity $state): string => $state->toReadableString())
                    ->color(fn (UpdateMaturity $state): string => $state->getDisplayColor())
                    ->sortable(),
            ])
            ->defaultSort('release', 'desc')
            ->actions([
                CustomAppComponents\Actions\Table\UpdateCoreAction::make(),
            ]);
    }

    public function render(): View
    {
        return view('livewire.app.core.available-updates');
    }
}
