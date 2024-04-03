<?php

namespace App\Filament\Resources\InstanceResource\Pages;

use App\Filament\Resources\InstanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Hydrat\TableLayoutToggle\Concerns\HasToggleableTable;
use Hydrat\TableLayoutToggle\Facades\TableLayoutToggle;

class ListInstances extends ListRecords
{
    use HasToggleableTable;

    protected static string $resource = InstanceResource::class;

    public function getDefaultLayoutView(): string
    {
        return 'grid'; // 'grid' || 'list'
    }

    protected function getHeaderActions(): array
    {
        return [
            TableLayoutToggle::getToggleViewAction(compact: false)
                ->hiddenLabel(false)
                ->label(fn ($livewire): string => $livewire->isGridLayout() ? __('List view') : __('Grid view')),
            Actions\CreateAction::make()
                ->icon('heroicon-o-plus-circle'),
        ];
    }
}
