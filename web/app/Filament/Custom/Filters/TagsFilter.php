<?php

namespace App\Filament\Custom\Filters;

use App\Models\Tag;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\Indicator;
use Illuminate\Database\Eloquent\Builder;

class TagsFilter
{
    public static function make(): Filter
    {
        return Filter::make('tags_filter')
            ->form([
                Grid::make('tags_filter')
                    ->label(__('Tags'))
                    ->schema([
                        Select::make('tags')
                            ->label(__('Tags'))
                            ->relationship('tags', 'name')
                            ->multiple()
                            ->searchable()
                            ->columnSpan(2),
                        Select::make('operator')
                            ->label(__('Operator'))
                            ->options([
                                'or' => 'OR',
                                'and' => 'AND',
                            ])
                            ->selectablePlaceholder(false)
                            ->default('or'),
                    ])->columns(3),
            ])
            ->columns()
            ->query(function (Builder $query, array $data): Builder {
                if (! empty($data['tags'])) {
                    $data['operator'] = $data['operator'] ?? 'or';
                    if ($data['operator'] == 'and') {
                        $query->whereHas('tags', function ($query) use ($data) {
                            $query->whereIn('tags.id', $data['tags']);
                        }, '=', count($data['tags']));
                    } else {
                        $query->whereHas('tags', function ($query) use ($data) {
                            $query->whereIn('tags.id', $data['tags']);
                        });
                    }
                }

                return $query;
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];

                if (! empty($data['tags'])) {
                    $data['operator'] = $data['operator'] ?? 'or';
                    $tags = Tag::whereIn('id', $data['tags'])->pluck('name');
                    if ($data['operator'] == 'and') {
                        $indicators[] = Indicator::make('tags_and_operation')
                            ->label(__('Tag: ').$tags->implode(', ').' | Operator: '.toUpper($data['operator']))
                            ->removeField('tags');
                    } else {
                        $indicators[] = Indicator::make('tags_and_operation')
                            ->label(__('Tag: ').$tags->implode(', ').' | Operator: '.toUpper($data['operator']))
                            ->removeField('tags');
                    }
                }

                return $indicators;
            });
    }
}
