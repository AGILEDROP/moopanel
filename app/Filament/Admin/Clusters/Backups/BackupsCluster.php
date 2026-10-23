<?php

namespace App\Filament\Admin\Clusters\Backups;

use App\Filament\Admin\Clusters\Backups\Pages\ChooseClusterPage;
use Filament\Clusters\Cluster;

class BackupsCluster extends Cluster
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 3;

    public function mount(): void
    {
        $this->redirect(ChooseClusterPage::getUrl());
    }
}
