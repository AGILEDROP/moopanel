<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupStorages\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupStorages\BackupStorageResource;
use Filament\Actions\CreateAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ListRecords;

class ListBackupStorages extends ListRecords
{
    protected static string $resource = BackupStorageResource::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-o-square-3-stack-3d'),
        ];
    }
}
