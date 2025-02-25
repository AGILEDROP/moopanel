<?php

namespace App\Livewire\App\Plugin;

use App\Filament\App\Resources\UpdateLogResource;
use App\Models\Plugin;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpdateLogTable extends Component implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    public Plugin $plugin;

    public function mount(Plugin $plugin): void
    {
        $this->plugin = $plugin;

        // Small hack responsible for refreshing the table when the modal opens (listener var don't work here)!
        $this->setPage(1, 'page');
    }

    public function table(Table $table): Table
    {
        return UpdateLogResource::table($table)
            ->query(fn () => $this->plugin->updateLog())
            ->description(null)
            ->headerActions([])
            ->deferLoading()
            ->paginationPageOptions([5, 10, 25]);
    }

    public function render(): View
    {
        return view('livewire.app.plugin.update-log-table');
    }
}
