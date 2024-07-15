<?php

namespace App\Filament\App\Widgets;

use App\Filament\App\Pages\UpdateRequests as PagesUpdateRequests;
use App\Models\UpdateRequest;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Model;

class UpdateRequests extends BaseWidget
{
    protected static ?int $sort = 4;

    protected int|string|array $columnSpan = [
        'sm' => 'full',
        'md' => 'full',
        'lg' => 4,
    ];

    public string $type = 'core';

    public int $maxItems = 5;

    public function table(Table $table): Table
    {
        return $table
            ->heading(__('Update Requests'))
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        UpdateRequest::TYPE_CORE => __('Core'),
                        UpdateRequest::TYPE_PLUGIN => __('Plugin'),
                        UpdateRequest::TYPE_PLUGIN_ZIP => __('Plugin ZIP'),
                    ]),
                SelectFilter::make('status')
                    ->options([
                        UpdateRequest::STATUS_SUCCESS => __('Success'),
                        UpdateRequest::STATUS_FAIL => __('Fail'),
                        UpdateRequest::STATUS_PENDING => __('Pending'),
                    ]),
            ], layout: FiltersLayout::Modal)
            ->query(
                UpdateRequest::where('instance_id', filament()->getTenant()->id)
                    ->orderBy('created_at', 'desc')
            )
            ->recordUrl(
                fn (Model $record): string => PagesUpdateRequests::getUrl().'?id='.((string) $record->id),
                true
            )
            ->actions([])
            ->columns([
                Split::make([
                    TextColumn::make('statusName')
                        ->label(__('Status'))
                        ->color(fn (Model $model) => is_null($model->status) ? 'warning' : ($model->status ? 'success' : 'danger'))
                        ->badge(),
                    TextColumn::make('type')
                        ->label(__('Type'))
                        ->color('gray')
                        ->badge(),
                    TextColumn::make('name')
                        ->label(__('Name'))
                        ->weight(FontWeight::Bold)
                        ->description(fn (Model $model): string => 'Short description of the update request'),
                    TextColumn::make('created_at')
                        ->since(),
                ])->from('sm'),
            ]);
    }
}
