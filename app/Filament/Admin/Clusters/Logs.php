<?php

namespace App\Filament\Admin\Clusters;

use Filament\Clusters\Cluster;

class Logs extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 6;
}
