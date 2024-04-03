<?php

namespace App\Filament\Clusters\Settings\Resources\UniversityMemberResource\Pages;

use App\Filament\Clusters\Settings\Resources\UniversityMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageUniversityMembers extends ManageRecords
{
    protected static string $resource = UniversityMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
