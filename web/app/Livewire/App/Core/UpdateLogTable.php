<?php

namespace App\Livewire\App\Core;

use App\Filament\App\Resources\UpdateLogResource;
use App\Filament\Custom\App as CustomAppComponents;
use App\Models\UpdateLog;
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

    protected $listeners = ['coreUpdateLogTableComponent' => '$refresh'];

    public function table(Table $table): Table
    {
        return UpdateLogResource::table($table)
            ->heading(__('Log'))
            ->description(CustomAppComponents\Actions\Table\SyncAction::getLastSyncTime('core-update-log'))
            ->query(UpdateLog::query()->whereNull('plugin_id'))
            ->headerActions([
                CustomAppComponents\Actions\Table\SyncAction::make('sync_core_update_log', 'core-update-log', 'coreUpdateLogTableComponent'),
            ]);
    }

    public function render(): View
    {
        return view('livewire.app.core.update-log-table');
    }
}
