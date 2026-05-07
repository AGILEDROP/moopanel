<?php

namespace App\Livewire\App\Plugin;

use Filament\Actions\Contracts\HasActions;
use Filament\Actions\Concerns\InteractsWithActions;
use App\Filament\App\Resources\UpdateLogs\UpdateLogResource;
use App\Models\Plugin;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class UpdateLogTable extends Component implements HasForms, HasTable, HasActions
{
    use InteractsWithActions;
    use InteractsWithForms;
    use InteractsWithTable;

    public Plugin $plugin;

    public function mount(Plugin $plugin): void
    {
        $this->plugin = $plugin;
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
