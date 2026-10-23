<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\Users\Pages;

use App\Filament\Admin\Clusters\UserManagement\Resources\Users\UserResource;
use App\Filament\Admin\Clusters\UserManagement\UserManagementCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Icon;
use Illuminate\Contracts\Support\Htmlable;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new Icon('heroicon-o-home'),
            UserManagementCluster::getUrl() => UserManagementCluster::getClusterBreadcrumb(),
            ManageUsers::getUrl() => __('Users'),
            //  self::getUrl(['record' => $this->getRecord()]) => $this->getRecord()->name,
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
