<?php

namespace App\Filament\App\Resources\UpdateLogResource\Pages;

use App\Filament\App\Resources\UpdateLogResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUpdateLogs extends ManageRecords
{
    protected static string $resource = UpdateLogResource::class;

    protected $listeners = ['manageUpdateLogPage' => '$refresh'];

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
