<?php

namespace App\Filament\Admin\Custom\Columns;

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
