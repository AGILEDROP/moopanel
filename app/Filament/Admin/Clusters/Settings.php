<?php

namespace App\Filament\Admin\Clusters;

use Filament\Clusters\Cluster;

class Settings extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 999;
}
