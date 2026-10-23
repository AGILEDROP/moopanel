<?php

namespace App\Filament\Admin\Clusters\Settings\Resources\Clusters\Pages;

use App\Filament\Admin\Clusters\Settings\Resources\Clusters\ClusterResource;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Actions\CreateAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Icon;

class ManageClusters extends ManageRecords
{
    protected static string $resource = ClusterResource::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new Icon('heroicon-o-home'),
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
