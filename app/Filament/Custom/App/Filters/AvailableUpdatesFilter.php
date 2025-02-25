<?php

namespace App\Filament\Custom\App\Filters;

use Filament\Tables\Filters\TernaryFilter;
use Illuminate\Database\Eloquent\Builder;

class AvailableUpdatesFilter
{
    public static function make(string $name): TernaryFilter
    {
        return TernaryFilter::make($name)
            ->label(__('Available updates'))
            ->placeholder(__('Select option'))
            ->trueLabel(__('Yes'))
            ->falseLabel(__('No'))
            ->queries(
                true: fn (Builder $query) => $query->whereHas('updates'),
                false: fn (Builder $query) => $query->whereDoesntHave('updates'),
                blank: fn (Builder $query) => $query, // In this example, we do not want to filter the query when it is blank.
            );
    }
}
