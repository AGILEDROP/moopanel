<?php

namespace App\Filament\App\Resources\UpdateLogs\Pages;

use App\Filament\App\Pages\AppDashboard;
use App\Filament\App\Resources\UpdateLogs\UpdateLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUpdateLogs extends ManageRecords
{
    protected static string $resource = UpdateLogResource::class;

    protected $listeners = ['manageUpdateLogPage' => '$refresh'];

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = (new AppDashboard)->getBreadcrumbs();
        $breadcrumbs[self::getUrl()] = UpdateLogResource::getNavigationLabel();

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
