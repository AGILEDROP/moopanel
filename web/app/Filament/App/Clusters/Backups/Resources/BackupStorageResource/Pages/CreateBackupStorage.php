<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupStorageResource\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupStorageResource;
use App\Models\BackupStorage;
use Filament\Resources\Pages\CreateRecord;

class CreateBackupStorage extends CreateRecord
{
    protected static string $resource = BackupStorageResource::class;

    protected function getRedirectUrl(): string
    {
        return ListBackupStorages::getUrl();
    }

    protected function beforeCreate(): void
    {
        $data = $this->form->getState();
        // Runs before the form fields are saved to the database.
        // If newly created record is active, mark other as inactive
        if (isset($data['active']) && $data['active']) {
            BackupStorage::where('instance_id', filament()->getTenant()->id)
                ->update(['active' => 0]);
        }

    }
}
