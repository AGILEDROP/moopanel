<?php

namespace App\Filament\Admin\Clusters\Settings\Resources\Clusters\Pages;

use App\Filament\Admin\Clusters\Settings\Resources\Clusters\ClusterResource;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Actions\CreateAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\HtmlString;

class ManageClusters extends ManageRecords
{
    protected static string $resource = ClusterResource::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            SettingsCluster::getUrl() => SettingsCluster::getClusterBreadcrumb(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
