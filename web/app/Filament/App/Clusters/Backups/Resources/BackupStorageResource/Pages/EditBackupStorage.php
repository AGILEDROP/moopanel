<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupStorageResource\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupStorageResource;
use App\Models\BackupStorage;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditBackupStorage extends EditRecord
{
    protected static string $resource = BackupStorageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->before(function (Action $action) {
                    $storageCount = BackupStorage::where('instance_id', filament()->getTenant()->id)->count();

                    if ($storageCount == 1) {

                        Notification::make()
                            ->title(__('Error'))
                            ->body(__('You can not delete the last storage.'))
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                })
                ->after(function () {
                    $data = $this->form->getState();

                    if (isset($data['active']) && $data['active']) {

                        $firstStorage = BackupStorage::where('instance_id', filament()->getTenant()->id)
                            ->orderBy('id', 'asc')
                            ->first();

                        if ($firstStorage) {
                            $firstStorage->update(['active' => 1]);
                        }
                    }
                })
                ->icon('heroicon-o-trash'),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return ListBackupStorages::getUrl();
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        if (isset($data['active'])) {
            if ($data['active']) {
                // uncheck all other storages in certain is selected
                BackupStorage::where('instance_id', $record->instance_id)
                    ->where('id', '!=', $record->id)
                    ->update(['active' => 0]);
            } else {
                // deactivate all storages
                BackupStorage::where('instance_id', $record->instance_id)
                    ->update(['active' => 0]);

                // set First storage as active
                BackupStorage::where('instance_id', $record->instance_id)
                    ->orderBy('id', 'asc')
                    ->first()
                    ->update(['active' => 1]);
            }
        }

        $record->update($data);

        return $record;
    }
}
