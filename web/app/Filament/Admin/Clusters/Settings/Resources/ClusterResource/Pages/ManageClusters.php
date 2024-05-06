<?php

namespace App\Filament\Admin\Clusters\Settings\Resources\ClusterResource\Pages;

use App\Filament\Admin\Clusters\Settings\Resources\ClusterResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageClusters extends ManageRecords
{
    protected static string $resource = ClusterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
