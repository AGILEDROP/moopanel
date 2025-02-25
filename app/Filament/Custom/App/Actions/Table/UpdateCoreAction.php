<?php

namespace App\Filament\Custom\App\Actions\Table;

use Filament\Tables\Actions\Action;

class UpdateCoreAction
{
    public static function make(string $name = 'update'): Action
    {
        return Action::make($name)
            ->label(__('Update'))
            ->icon('heroicon-o-arrow-up-circle')
            ->iconButton()
            // TODO: when endpoint will be created, add logic for updating instance core here!
            ->action(fn () => dd('Implement core update action'));
    }
}
