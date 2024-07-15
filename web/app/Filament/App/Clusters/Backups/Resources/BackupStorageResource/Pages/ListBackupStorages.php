<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupStorageResource\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupStorageResource;
use Filament\Actions;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Pages\ListRecords;

class ListBackupStorages extends ListRecords
{
    protected static string $resource = BackupStorageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->icon('heroicon-o-square-3-stack-3d'),
        ];
    }

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
