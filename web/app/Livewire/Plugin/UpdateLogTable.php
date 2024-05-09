<?php

namespace App\Livewire\Plugin;

use App\Enums\UpdateLogType;
use App\Models\Plugin;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;
use Livewire\Component;

class UpdateLogTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Plugin $plugin;

    public function mount(Plugin $plugin): void
    {
        $this->plugin = $plugin;

        // Small hack responsible for refreshing the table when the modal opens!
        $this->setPage(1, 'page');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => $this->plugin->updateLog())
            ->columns([
                Tables\Columns\TextColumn::make('info')
                    ->searchable()
                    ->sortable(),
                //                Tables\Columns\TextColumn::make('instance.name')
                //                    ->searchable()
                //                    ->sortable(),
                Tables\Columns\TextColumn::make('timemodified')
                    ->label(__('Time modified'))
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->label(__('Modified by'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Operation result'))
                    ->formatStateUsing(fn (UpdateLogType $state): string => $state->toReadableString())
                    ->color(fn (UpdateLogType $state): string => $state->toDisplayColor())
                    ->icon(fn (UpdateLogType $state): string => $state->toDisplayIcon())
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label(__('Version'))
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('show_details')
                    ->hiddenLabel()
                    ->icon('heroicon-o-bug-ant')
                    ->hidden(fn (Model $record): bool => $record->details === null)
                    ->modalHeading(__('Details & Backtrace'))
                    ->stickyModalHeader()
                    ->modalContent(fn (Model $record) => new HtmlString('<div><b>Details:</b><br/>'.$record->details.'</div><div><b>Backtrace:</b><br/>'.$record->backtrace.'</div></div>'))
                    ->modalSubmitAction(false)
                    ->modalCancelAction(false),
            ])
            ->deferLoading()
            ->defaultSort('timemodified', 'desc')
            ->paginationPageOptions([5, 10, 25]);
    }

    public function render(): View
    {
        return view('livewire.plugin.update-log-table');
    }
}
