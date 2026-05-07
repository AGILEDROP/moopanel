<?php

namespace App\Filament\App\Resources;

use Filament\Tables\Columns\TextColumn;
use App\Filament\App\Resources\UpdateLogResource\Pages\ManageUpdateLogs;
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

    protected static string | \BackedEnum | null $navigationIcon = 'fas-timeline';

    public static function table(Table $table): Table
    {
        $syncType = SyncTypeFactory::create(UpdateLogSyncType::TYPE, Instance::find(filament()->getTenant()->id));

        return $table
            ->description($syncType->getLatestTimeText())
            ->columns([
                TextColumn::make('plugin_exists')
                    ->visibleOn(ManageUpdateLogs::class)
                    ->label('Type')
                    ->exists('plugin')
                    ->formatStateUsing(fn ($state) => $state === true ? __('Plugin') : __('Core'))
                    ->icon(fn ($state) => $state === true ? 'fas-plug' : 'fas-cube')
                    ->color('gray')
                    ->badge()
                    ->sortable(),
                TextColumn::make('plugin.display_name')
                    ->visibleOn(ManageUpdateLogs::class)
                    ->toggleable(fn ($livewire) => ($livewire instanceof ManageUpdateLogs), isToggledHiddenByDefault: false)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('info')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('timemodified')
                    ->label(__('Time modified'))
                    ->sortable()
                    ->dateTime(),
                TextColumn::make('username')
                    ->searchable()
                    ->label(__('Modified by'))
                    ->sortable(),
                TextColumn::make('version')
                    ->label(__('Version'))
                    ->sortable(),
                TextColumn::make('targetversion')
                    ->label(__('Target version'))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
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
            ->recordActions([
                CustomAppComponents\Actions\Table\UpdateLogDetailsAction::make(),
            ])
            ->filters([
                CustomAppComponents\Filters\UpdateLogTypeFilter::make('main')
                    ->visibleOn(ManageUpdateLogs::class),
            ])
            ->defaultSort('timemodified', 'desc')
            ->paginationPageOptions([5, 10, 25, 50]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUpdateLogs::route('/'),
        ];
    }
}
