<?php

namespace App\Livewire\App\Core;

use App\Enums\UpdateMaturity;
use App\Enums\UpdateType;
use App\Jobs\Update\CoreUpdateRequestJob;
use App\Models\Instance;
use App\Models\Update;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Tables;
use Filament\Tables\Actions\Action;
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
                Action::make('update')
                    ->label(__('Update'))
                    ->requiresConfirmation()
                    ->modalDescription(__('Are you sure you want to perform the core update of the selected instance?'))
                    ->modalIcon('fas-cube')
                    ->icon('fas-cube')
                    ->action(function (Update $update) {
                        $instance = $update->instance;
                        $user = auth()->user();

                        if (! $update || ! $user) {
                            Notification::make()
                                ->danger()
                                ->title(__('Update not found'))
                                ->send();

                            return;
                        }

                        $payload = [
                            'user_id' => $user->id,
                            'instance_id' => $update->instance_id,
                            'update_id' => $update->id,
                            'type' => 'core',
                            'version' => $update->version,
                            'release' => $update->release,
                            'url' => $update->url,
                            'download' => $update->download,
                        ];

                        CoreUpdateRequestJob::dispatch($instance, $update, $user, $payload);

                        Notification::make()
                            ->success()
                            ->title(__('Core update request submitted'))
                            ->icon('fas-cube')
                            ->iconColor('success')
                            ->send();
                    }),
            ]);
    }

    public function render(): View
    {
        return view('livewire.app.core.available-updates');
    }
}
