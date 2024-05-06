<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages;

use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    public function getHeading(): string|Htmlable
    {
        return $this->getRecord()->name;
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
