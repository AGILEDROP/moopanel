<?php

namespace App\Livewire\App\Core;

use App\Filament\App\Resources\UpdateLogResource;
use App\Models\Instance;
use App\Models\UpdateLog;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
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
        $syncType = SyncTypeFactory::create(CoreSyncType::TYPE, Instance::find(filament()->getTenant()->id));

        return UpdateLogResource::table($table)
            ->heading(__('Log'))
            ->description($syncType->getLatestTimeText())
            ->query(UpdateLog::query()->whereNull('plugin_id'))
            ->headerActions([
                //
            ]);
    }

    public function render(): View
    {
        return view('livewire.app.core.update-log-table');
    }
}
