<?php

namespace App\Filament\Custom\Columns;

use Filament\Tables\Columns\TextColumn;

class IdColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? $name)
            ->numeric()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}
