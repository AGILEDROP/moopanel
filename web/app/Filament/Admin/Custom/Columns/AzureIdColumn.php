<?php

namespace App\Filament\Admin\Custom\Columns;

use Filament\Tables\Columns\TextColumn;

class AzureIdColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? $name)
            ->copyable()
            ->toggleable();
    }
}
