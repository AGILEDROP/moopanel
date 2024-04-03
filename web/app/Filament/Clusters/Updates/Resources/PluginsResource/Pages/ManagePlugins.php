<?php

namespace App\Filament\Clusters\Updates\Resources\PluginsResource\Pages;

use App\Filament\Clusters\Updates\Resources\PluginsResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePlugins extends ManageRecords
{
    protected $listeners = ['updateManagePluginsPage' => '$refresh'];

    protected static string $resource = PluginsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
