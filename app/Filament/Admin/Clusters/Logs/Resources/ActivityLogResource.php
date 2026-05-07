<?php

namespace App\Filament\Admin\Clusters\Logs\Resources;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Logs;
use App\Filament\Admin\Clusters\Logs\Resources\ActivityLogResource\Pages;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class ActivityLogResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static ?string $cluster = Logs::class;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $navigationIcon = 'fas-clipboard-list';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function canAccess(): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('log_name')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('event')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->toggleable(),
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('subject_type')
                    ->label('Subject')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('causer_type')
                    ->label('Causer')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : 'System')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Logged at')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('log_name')
                    ->options(fn (): array => Activity::query()
                        ->select('log_name')
                        ->distinct()
                        ->orderBy('log_name')
                        ->pluck('log_name', 'log_name')
                        ->filter()
                        ->all()),
                Tables\Filters\SelectFilter::make('event')
                    ->options(fn (): array => Activity::query()
                        ->select('event')
                        ->distinct()
                        ->orderBy('event')
                        ->pluck('event', 'event')
                        ->filter()
                        ->all()),
            ])
            ->actions([
                Tables\Actions\Action::make('details')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (Activity $record): string => 'Activity #'.$record->getKey())
                    ->modalSubmitAction(false)
                    ->infolist(fn (Activity $record): array => [
                        Section::make()
                            ->schema([
                                TextEntry::make('log_name')->state($record->log_name ?: '-'),
                                TextEntry::make('event')->state($record->event ?: '-'),
                                TextEntry::make('description')->state($record->description ?: '-'),
                                TextEntry::make('subject')->state($record->subject_type ? class_basename($record->subject_type).' #'.$record->subject_id : '-'),
                                TextEntry::make('causer')->state($record->causer_type ? class_basename($record->causer_type).' #'.$record->causer_id : 'System'),
                                TextEntry::make('created_at')->label('Logged at')->state(optional($record->created_at)?->format('d/m/Y H:i:s') ?: '-'),
                                TextEntry::make('properties')
                                    ->state(static::formatProperties($record)),
                            ])
                            ->columns(1),
                    ]),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist;
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListActivityLogs::route('/'),
        ];
    }

    protected static function formatProperties(Activity $record): string
    {
        $properties = $record->properties?->toArray() ?? [];

        if ($properties === []) {
            return '{}';
        }

        return json_encode($properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}';
    }
}
