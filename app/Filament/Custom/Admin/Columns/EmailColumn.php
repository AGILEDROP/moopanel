<?php

namespace App\Filament\Custom\Admin\Columns;

use Filament\Tables\Columns\TextColumn;

class EmailColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? $name)
            ->searchable()
            ->copyable();
    }
}
