<?php

namespace App\Filament\Admin\Clusters\Settings;

use Filament\Clusters\Cluster;

class SettingsCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 999;
}
