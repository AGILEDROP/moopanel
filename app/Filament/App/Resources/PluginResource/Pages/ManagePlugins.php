<?php

namespace App\Filament\App\Resources\PluginResource\Pages;

use App\Filament\App\Pages\AppDashboard;
use App\Filament\App\Resources\PluginResource;
use Filament\Resources\Pages\ManageRecords;

class ManagePlugins extends ManageRecords
{
    protected static string $resource = PluginResource::class;

    protected $listeners = ['managePluginsPage' => '$refresh'];

    protected ?string $heading = 'Plugins';

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = (new AppDashboard)->getBreadcrumbs();
        $breadcrumbs[self::getUrl()] = PluginResource::getNavigationLabel();

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
