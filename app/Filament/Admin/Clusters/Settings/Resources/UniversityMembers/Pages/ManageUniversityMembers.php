<?php

namespace App\Filament\Admin\Clusters\Settings\Resources\UniversityMembers\Pages;

use App\Filament\Admin\Clusters\Settings\Resources\UniversityMembers\UniversityMemberResource;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Actions\CreateAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Icon;

class ManageUniversityMembers extends ManageRecords
{
    protected static string $resource = UniversityMemberResource::class;

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
