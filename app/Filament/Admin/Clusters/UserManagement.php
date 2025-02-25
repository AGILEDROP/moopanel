<?php

namespace App\Filament\Admin\Clusters;

use App\Enums\Role;
use App\Filament\Admin\Clusters\UserManagement\Resources\AccountResource\Pages\ManageAccounts;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages\ManageUsers;
use Filament\Clusters\Cluster;

class UserManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

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
