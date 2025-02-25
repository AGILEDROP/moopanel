<?php

namespace App\Filament\Custom\App\Actions\Table;

use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class UpdateLogDetailsAction
{
    public static function make(string $name = 'show_details'): Action
    {
        return Action::make($name)
            ->hiddenLabel()
            ->slideOver()
            ->icon('heroicon-o-bug-ant')
            ->hidden(fn (Model $record): bool => $record->details === null)
            ->modalHeading(__('Details & Backtrace'))
            ->stickyModalHeader()
            ->modalContent(fn (Model $record) => new HtmlString('
                <div><b>'.__('Details').':</b><br/>'.$record->details.'</div>
                <div><b>'.__('Backtrace').':</b><br/>'.$record->backtrace.'</div>'))
            ->modalSubmitAction(false)
            ->modalCancelAction(false);
    }
}
