<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages;

use App\Filament\Admin\Clusters\UserManagement;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource;
use App\Filament\Admin\Resources\InstanceResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

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
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            UserManagement::getUrl() => UserManagement::getClusterBreadcrumb(),
            ManageUsers::getUrl() => __('Users'),
            //  self::getUrl(['record' => $this->getRecord()]) => $this->getRecord()->name,
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
