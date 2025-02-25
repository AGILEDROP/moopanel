<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages;

use App\Filament\Admin\Clusters\UserManagement;
use App\Filament\Admin\Clusters\UserManagement\Resources\AccountResource\Pages\ManageAccounts;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource;
use App\Filament\Admin\Resources\InstanceResource;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            UserManagement::getUrl() => UserManagement::getClusterBreadcrumb(),
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return new HtmlString('<a href="'.ManageAccounts::getUrl().'" class="mt-4 relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-full gap-1.5 px-5 py-2 text-sm inline-grid shadow-sm bg-gray-900 text-white hover:bg-gray-800 focus-visible:ring-gray-800/50 dark:bg-primary-500 dark:hover:bg-primary-400 dark:focus-visible:ring-primary-400/50">
                <span class="flex font-bold">'.__('Accounts').'
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="ms-1.5 w-5 h-5">
                      <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </span>
            </a>');
    }

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
