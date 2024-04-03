<?php

namespace App\Filament\Custom\Columns;

use Filament\Support\Enums\IconPosition;
use Filament\Tables\Columns\TextColumn;

class UpnColumn
{
    public static function make(string $name, ?string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? $name)
            ->searchable()
            ->copyable()
            ->icon('heroicon-m-document-duplicate')
            ->iconPosition(IconPosition::After);
    }
}
