<?php

namespace App\Filament\Custom\Admin\Columns;

use Filament\Tables\Columns\TextColumn;

class SortableDateTimeColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? $name)
            ->dateTime()
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
