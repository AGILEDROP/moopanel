<?php

namespace App\Filament\Admin\Clusters;

use App\Filament\Admin\Clusters\Updates\Pages\ChooseClusterPage;
use Filament\Clusters\Cluster;

class Updates extends Cluster
{
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-up-circle';

    protected static ?int $navigationSort = 5;

    public function mount(): void
    {
        $this->redirect(ChooseClusterPage::getUrl());
    }
}
