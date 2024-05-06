<?php

namespace App\Filament\Admin\Clusters;

use Filament\Clusters\Cluster;

class UserManagement extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;
}
