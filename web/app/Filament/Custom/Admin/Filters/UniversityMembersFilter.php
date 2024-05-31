<?php

namespace App\Filament\Custom\Admin\Filters;

use Filament\Tables\Filters\SelectFilter;

class UniversityMembersFilter
{
    public static function make(string $name, ?string $relationship = null): SelectFilter
    {
        return SelectFilter::make($name)
            ->label(__('University member'))
            ->multiple()
            ->relationship($relationship ?? $name, 'name')
            ->searchable();
    }
}
