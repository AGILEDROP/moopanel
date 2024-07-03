<?php

namespace App\Filament\App\Clusters\Backups\Resources;

use App\Filament\App\Clusters\Backups;
use App\Filament\App\Clusters\Backups\Resources\BackupResultResource\Pages;
use App\Filament\App\Clusters\Backups\Resources\BackupResultResource\Pages\ViewBackupResult;
use App\Filament\App\Clusters\Backups\Resources\BackupResultResource\RelationManagers;
use App\Models\BackupResult;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BackupResultResource extends Resource
{
    protected static ?string $model = BackupResult::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $cluster = Backups::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('statusName')
                    ->label(__('Status'))
                    ->color(fn (Model $model) => is_null($model->status) ? 'warning' : ($model->status ? 'success' : 'danger'))
                    ->badge(),
                TextColumn::make('course.name')
                    ->label(__('Course name'))
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Model $record) => $record->course->category->name)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->color('info')
                    ->badge(),
                // TODO: ali se na instanic prikaže samo sprožene backupe tega uporabnika, ali vseh uporabnikov
                TextColumn::make('user.name')
                    ->label(__('Triggered by'))
                    ->weight(FontWeight::SemiBold)
                    ->state(function (BackupResult $record): string {
                        if ($record->user_id) {
                            return $record->user->name;
                        }

                        return 'Cron';
                    })
                    ->description(
                        function (BackupResult $record): string {
                            $prefix = __('At ');

                            if ($record->manual_trigger_timestamp) {
                                return $prefix . Carbon::createFromTimestamp($record->manual_trigger_timestamp)
                                    ->format('Y-m-d H:i:s');
                            }

                            return $prefix . '-';
                        }
                    )
                    ->default('Automatic')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('message')
                    ->lineClamp(3)
                    ->wrap()
                    ->default('-'),
                TextColumn::make('password')
                    ->copyable()
                    ->copyMessage(__('Password copied to clipboard'))
                    ->copyMessageDuration(1500)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),
                TextColumn::make('updated_at')
                    ->label(__('Last updated'))
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Action::make('download')
                    ->url(fn (BackupResult $record): string => $record->url ?? "#")
                    ->openUrlInNewTab()
                    ->color(function (BackupResult $record): string {
                        if (is_null($record->status) || !$record->status || is_null($record->url)) {
                            return 'grey';
                        }

                        return 'success';
                    })
                    ->iconButton()
                    ->disabled(function (BackupResult $record): bool {
                        if (is_null($record->status) || !$record->status || is_null($record->url)) {
                            return true;
                        }

                        return false;
                    })
                    ->icon('heroicon-m-arrow-down-tray')
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackupResults::route('/'),
        ];
    }
}
