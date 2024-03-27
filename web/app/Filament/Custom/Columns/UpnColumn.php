<?php

namespace App\Filament\Custom\Columns;

use Filament\Tables\Columns\TextColumn;

class UpnColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? $name)
            ->searchable()
            ->copyable();
    }
}
