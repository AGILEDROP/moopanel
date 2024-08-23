<?php

namespace App\Filament\App\Resources;

use App\Enums\UpdateLogType;
use App\Filament\App\Resources\UpdateLogResource\Pages;
use App\Filament\Custom\App as CustomAppComponents;
use App\Models\Instance;
use App\Models\UpdateLog;
use App\UseCases\Syncs\SingleInstance\UpdateLogSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UpdateLogResource extends Resource
{
    protected static ?string $model = UpdateLog::class;

    protected static ?string $navigationIcon = 'fas-timeline';

    public static function table(Table $table): Table
    {
        $syncType = SyncTypeFactory::create(UpdateLogSyncType::TYPE, Instance::find(filament()->getTenant()->id));

        return $table
            ->description($syncType->getLatestTimeText())
            ->columns([
                Tables\Columns\TextColumn::make('plugin_exists')
                    ->visibleOn(Pages\ManageUpdateLogs::class)
                    ->label('Type')
                    ->exists('plugin')
                    ->formatStateUsing(fn ($state) => $state === true ? __('Plugin') : __('Core'))
                    ->icon(fn ($state) => $state === true ? 'fas-plug' : 'fas-cube')
                    ->color('gray')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('plugin.display_name')
                    ->visibleOn(Pages\ManageUpdateLogs::class)
                    ->toggleable(fn ($livewire) => ($livewire instanceof Pages\ManageUpdateLogs), isToggledHiddenByDefault: false)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('info')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('timemodified')
                    ->label(__('Time modified'))
                    ->sortable()
                    ->dateTime(),
                Tables\Columns\TextColumn::make('username')
                    ->searchable()
                    ->label(__('Modified by'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('version')
                    ->label(__('Version'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('targetversion')
                    ->label(__('Target version'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Operation result'))
                    ->formatStateUsing(fn (UpdateLogType $state): string => $state->toReadableString())
                    ->color(fn (UpdateLogType $state): string => $state->toDisplayColor())
                    ->icon(fn (UpdateLogType $state): string => $state->toDisplayIcon())
                    ->badge()
                    ->sortable(),
            ])
            ->headerActions([
                $syncType->getTableAction('sync', ['manageUpdateLogPage']),
            ])
            ->actions([
                CustomAppComponents\Actions\Table\UpdateLogDetailsAction::make(),
            ])
            ->filters([
                CustomAppComponents\Filters\UpdateLogTypeFilter::make('main')
                    ->visibleOn(Pages\ManageUpdateLogs::class),
            ])
            ->defaultSort('timemodified', 'desc')
            ->paginationPageOptions([5, 10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUpdateLogs::route('/'),
        ];
    }
}
