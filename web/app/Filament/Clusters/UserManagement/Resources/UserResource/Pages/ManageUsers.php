<?php

namespace App\Filament\Clusters\UserManagement\Resources\UserResource\Pages;

use App\Filament\Clusters\UserManagement\Resources\UserResource;
use Filament\Resources\Pages\ManageRecords;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
        ];
    }
}
