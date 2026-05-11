<?php

namespace App\Filament\Admin\Clusters\Logs;

use Filament\Clusters\Cluster;

class LogsCluster extends Cluster
{
    protected static ?string $slug = 'log-management';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static ?int $navigationSort = 6;
}
