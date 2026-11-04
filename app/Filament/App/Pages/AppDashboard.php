<?php

namespace App\Filament\App\Pages;

use Filament\Pages\Dashboard;
use Filament\Schemas\Components\Icon;

class AppDashboard extends Dashboard
{
    public function getColumns(): int|array
    {
        return 8;
    }

    public function getBreadcrumbs(): array
    {
        return [
            route('filament.admin.resources.instances.index') => new Icon('heroicon-o-home'),
            AppDashboard::getUrl() => filament()->getTenant()->name." test",
        ];
    }
}
