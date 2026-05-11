<?php

namespace App\Filament\Admin\Clusters\UserManagement;

use App\Enums\Role;
use App\Filament\Admin\Clusters\UserManagement\Resources\Accounts\Pages\ManageAccounts;
use App\Filament\Admin\Clusters\UserManagement\Resources\Users\Pages\ManageUsers;
use Filament\Clusters\Cluster;

class UserManagementCluster extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 2;

    public function mount(): void
    {
        if (auth()->user()->role() === Role::MasterAdmin) {
            $this->redirect(ManageUsers::getUrl());
        } else {
            $this->redirect(ManageAccounts::getUrl());
        }
    }
}
