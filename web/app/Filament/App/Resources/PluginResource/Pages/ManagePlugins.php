<?php

namespace App\Filament\App\Resources\PluginResource\Pages;

use App\Filament\App\Resources\PluginResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePlugins extends ManageRecords
{
    protected static string $resource = PluginResource::class;

    protected $listeners = ['managePluginsPage' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
