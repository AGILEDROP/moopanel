<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\Accounts\Pages;

use App\Enums\Role;
use App\Filament\Admin\Clusters\UserManagement\Resources\Accounts\AccountResource;
use App\Filament\Admin\Clusters\UserManagement\Resources\Users\Pages\ManageUsers;
use App\Filament\Admin\Clusters\UserManagement\UserManagementCluster;
use App\Filament\Admin\Resources\Instances\InstanceResource;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Icon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ManageAccounts extends ManageRecords
{
    protected static string $resource = AccountResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new Icon('heroicon-o-home'),
            UserManagementCluster::getUrl() => UserManagementCluster::getClusterBreadcrumb(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        if (auth()->user()->role() === Role::MasterAdmin) {
            return new HtmlString('<a href="'.ManageUsers::getUrl().'" class="mt-4 relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-full gap-1.5 px-5 py-2 text-sm inline-grid shadow-sm bg-gray-900 text-white hover:bg-gray-800 focus-visible:ring-gray-800/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50">
                <span class="flex font-bold">'.__('Users').'
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="ms-1.5 w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
            </a>');
        }

        return null;
    }
}
