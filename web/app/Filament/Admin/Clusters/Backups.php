<?php

namespace App\Filament\Admin\Clusters;

use App\Filament\Admin\Clusters\Backups\Pages\ChooseClusterPage;
use Filament\Clusters\Cluster;

class Backups extends Cluster
{
    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?int $navigationSort = 3;

    public function mount(): void
    {
        $this->redirect(ChooseClusterPage::getUrl());
    }
}
