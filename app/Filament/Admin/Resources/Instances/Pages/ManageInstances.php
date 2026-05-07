<?php

namespace App\Filament\Admin\Resources\Instances\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Admin\Pages\AddInstance;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Hydrat\TableLayoutToggle\Facades\TableLayoutToggle;

class ManageInstances extends ManageRecords
{
    use HasToggleableTable;

    protected static string $resource = InstanceResource::class;

    protected ?string $heading = 'Home';

    public function getDefaultLayoutView(): string
    {
        // 'grid' || 'list'
        return 'grid';
    }

    protected function getHeaderActions(): array
    {
        return [
            TableLayoutToggle::getToggleViewAction(compact: false)
                ->hiddenLabel(false)
                ->label(fn ($livewire): string => $livewire->isGridLayout() ? __('List view') : __('Grid view')),
            CreateAction::make()
                ->icon('heroicon-o-plus-circle')
                ->url(AddInstance::getUrl()),
        ];
    }
}
