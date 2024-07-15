<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupSettingResource\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupSettingResource;
use App\Models\BackupSetting;
use Filament\Resources\Pages\ListRecords;

class ListBackupSettings extends ListRecords
{
    protected static string $resource = BackupSettingResource::class;

    public function mount(): void
    {
        // Redirect to edit page since we have only 1 setting per instance
        $record = BackupSetting::where('instance_id', filament()->getTenant()->id)->first();
        $this->redirect(EditBackupSetting::getUrl(['record' => $record]));
    }
}
