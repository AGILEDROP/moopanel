<?php

namespace App\Filament\Custom\App\Filters;

use App\Models\Plugin;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;

class InstancePluginTypeFilter
{
    public static function make(string $name): Filter
    {
        return Filter::make($name)
            ->label(__('Type'))
            ->form([
                Select::make('type')
                    ->options(fn () => Plugin::distinct('type')->pluck('type', 'type'))
                    ->searchable(),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['type'],
                        function (Builder $query, string $type) {
                            $ids = Plugin::where('type', $type)->pluck('id');

                            return $query->whereIn('plugin_id', $ids);
                        }
                    );
            })
            ->indicateUsing(function (array $data): ?string {
                if (! $data['type']) {
                    return null;
                }

                return __('Type: :type', ['type' => $data['type']]);
            });
    }
}
