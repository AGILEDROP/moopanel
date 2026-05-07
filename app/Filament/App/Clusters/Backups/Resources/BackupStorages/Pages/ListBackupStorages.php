<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupStorages\Pages;

use Filament\Actions\CreateAction;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\App\Clusters\Backups\Resources\BackupStorages\BackupStorageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBackupStorages extends ListRecords
{
    protected static string $resource = BackupStorageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-square-3-stack-3d'),
        ];
    }

    public static function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
