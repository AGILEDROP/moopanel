<?php

namespace App\Filament\Custom\App\Filters;

use App\Models\Plugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

class UpdateLogTypeFilter
{
    public static function make(string $name): Filter
    {
        return Filter::make($name)
            ->form([
                Select::make('type')
                    ->options([
                        'plugin' => __('Plugin'),
                        'core' => __('Core'),
                    ])
                    ->afterStateUpdated(function ($state, Set $set) {
                        if ($state === 'core') {
                            $set('plugin_ids', null);
                        }
                    }),
                Select::make('plugin_ids')
                    ->label(__('Plugins'))
                    ->live()
                    ->hidden(fn (Get $get) => $get('type') !== 'plugin')
                    ->relationship('plugin', 'display_name')
                    ->multiple()
                    ->searchable(),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['type'],
                        function (Builder $query, string $type) {
                            if ($type === 'plugin') {
                                $query->whereHas('plugin');
                            } elseif ($type === 'core') {
                                $query->whereDoesntHave('plugin');
                            }

                            return $query;
                        }
                    )
                    ->when(
                        $data['plugin_ids'],
                        function (Builder $query, array $plugin_ids) {
                            $query->whereIn('plugin_id', $plugin_ids);

                            return $query;
                        }
                    );
            })
            ->indicateUsing(function (array $data) use ($name): array {
                $indicators = [];

                if ($data['type'] ?? null) {
                    $typeLabel = $data['type'] == 'plugin' ? __('plugin') : __('core');

                    $indicators[] = Indicator::make('Type: '.$typeLabel)
                        ->removeField($name);
                }

                if ($data['plugin_ids'] ?? null) {
                    $pluginNames = Plugin::whereIn('id', $data['plugin_ids'])->pluck('display_name')->implode(', ');

                    $indicators[] = Indicator::make('Plugins: '.$pluginNames)
                        ->removeField('plugin_ids');
                }

                return $indicators;
            });
    }
}
