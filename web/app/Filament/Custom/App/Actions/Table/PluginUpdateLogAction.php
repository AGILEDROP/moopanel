<?php

namespace App\Filament\Custom\App\Actions\Table;

use App\Livewire\App\Plugin\UpdateLogTable;
use Filament\Infolists\Components\Livewire;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;

class PluginUpdateLogAction
{
    public static function make(string $name = 'plugin_log'): Action
    {
        return Action::make($name)
            ->iconButton()
            ->visible(fn (Model $record): bool => $record->updateLog()->count() > 0)
            // One way to show custom livewire table inside modal window. You can also use modalContent,
            // but then there you need two views for same table.
            ->infolist(fn (Model $record) => [
                Livewire::make(UpdateLogTable::class, ['plugin' => $record->plugin])
                    ->key('plugin-log-'.$record->id)
                    ->id('plugin-log-'.$record->id),
            ])
            ->modalHeading(fn (Model $record) => __('Plugin log: :plugin', ['plugin' => $record->plugin->display_name]))
            ->modalCancelAction(false)
            ->modalSubmitAction(false)
            ->slideOver()
            ->icon('fas-timeline')
            ->color('gray');
    }
}
