<?php

namespace App\Filament\Custom\Admin\Actions\Forms;

use Filament\Forms\Components\Actions\Action;

class CopyFieldStateAction
{
    public static function make(string $name, ?string $label = null): Action
    {
        return Action::make($name)
            ->label($label ?? $name)
            ->icon('heroicon-m-clipboard')
            ->action(function ($livewire, $state) {
                $livewire->js('window.navigator.clipboard.writeText("'.$state.'"); $tooltip("Copied to clipboard", { timeout: 1500 });');
            });
    }
}
