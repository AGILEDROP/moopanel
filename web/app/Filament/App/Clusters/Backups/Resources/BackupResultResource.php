<?php

namespace App\Filament\App\Clusters\Backups\Resources;

use App\Enums\BackupStorageType;
use App\Filament\App\Clusters\Backups;
use App\Filament\App\Clusters\Backups\Resources\BackupResultResource\Pages;
use App\Helpers\StringHelper;
use App\Jobs\Backup\DeleteOldBackupsJob;
use App\Jobs\Backup\RestoreBackupJob;
use App\Models\BackupResult;
use Carbon\Carbon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

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
            ->query(
                fn () => BackupResult::where('instance_id', filament()->getTenant()->id)

                // TBD: You can only show backups of current user
                // makes sense to show also other backups since user can then download id directly withou creating another request
                /* ->where(function ($query) {
                        return $query->whereNull('user_id')
                            ->orWhere('user_id', auth()->user()->id);
                    }) */
            )
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
                    ->wrap()
                    ->sortable(),
                TextColumn::make('type')
                    ->color('info')
                    ->badge(),
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
                                return $prefix.Carbon::createFromTimestamp($record->manual_trigger_timestamp)
                                    ->format('Y-m-d H:i:s');
                            }

                            // General timestamp when backup result was created
                            return $prefix.$record->created_at->format('Y-m-d H:i:s');
                        }
                    )
                    ->default('Automatic')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('backupStorage.name')
                    ->label(__('Storage'))
                    ->limit(10)
                    ->badge()
                    ->icon('heroicon-o-circle-stack')
                    ->color('gray')
                    ->tooltip(fn (BackupResult $record): string => $record->backupStorage ? $record->backupStorage->name : __('Local storage'))
                    ->url(
                        function (BackupResult $record): ?string {
                            $record = $record->backupStorage;

                            if (! $record) {
                                return null;
                            }

                            return route(
                                'filament.app.backups.resources.backup-storages.edit',
                                [
                                    'tenant' => filament()->getTenant(),
                                    'record' => $record,
                                ]
                            );
                        }
                    )
                    ->openUrlInNewTab(),
                TextColumn::make('url')
                    ->label(__('File path'))
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable(),
                TextColumn::make('filesize')
                    ->label(__('File size'))
                    ->numeric()
                    ->formatStateUsing(fn (int $state): string => StringHelper::fileSizeConvert($state))
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderBy('filesize', $direction);
                    })
                    ->color('gray')
                    ->badge(),
                TextColumn::make('message')
                    ->lineClamp(3)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->default('-'),
                TextColumn::make('password')
                    ->copyable()
                    ->copyMessage(__('Password copied to clipboard'))
                    ->tooltip(__('Click to copy password'))
                    ->copyMessageDuration(2000)
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyableState(function (Model $record): string {
                        try {
                            $password = Crypt::decrypt($record->password);
                        } catch (\Exception $e) {
                            return 'invalid-password';
                        }

                        return $password;
                    })
                    ->formatStateUsing(fn ($state) => str_repeat('*', 8))
                    ->placeholder('-'),
                TextColumn::make('updated_at')
                    ->label(__('Last updated'))
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                //
            ])
            ->paginated([10, 25, 50])
            ->actions([
                ActionGroup::make([
                    Action::make('restore')
                        ->requiresConfirmation()
                        ->color('success')
                        ->hidden(function (BackupResult $record): bool {
                            if (is_null($record->status) || ! $record->status || is_null($record->url)) {
                                return true;
                            }

                            return false;
                        })
                        ->form([
                            TextInput::make('password')
                                ->label('File password')
                                ->required(),
                        ])
                        ->action(function (array $data, BackupResult $record): void {
                            if ($record->in_deletion_process) {
                                Notification::make()
                                    ->title(__('Backup restore error'))
                                    ->body(__('Backup is in deletion process. Please wait until the deletion process is completed.'))
                                    ->danger()
                                    ->seconds(8)
                                    ->send();

                                return;
                            }

                            if (BackupResult::where('moodle_course_id', $record->moodle_course_id)->where('in_restore_process', true)->exists()) {
                                Notification::make()
                                    ->title(__('Backup restore error'))
                                    ->body(__('There\'s an ongoing restore process on selected course. Please wait until it is completed.'))
                                    ->danger()
                                    ->seconds(8)
                                    ->send();

                                return;
                            }

                            if (! isset($data['password'])) {
                                Notification::make()
                                    ->title(__('Backup restore error'))
                                    ->body(__('Password is required'))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            try {
                                $decryptedPassword = Crypt::decrypt($record->password);
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title(__('Backup restore error'))
                                    ->body(__('Password is incorrect'))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            RestoreBackupJob::dispatch($record, $data['password'], auth()->user());

                            Notification::make()
                                ->title(__('Backup restore in progress'))
                                ->body(__('Backup restore for course :course is in progress.', ['course' => $record->course->name]))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-m-arrow-path')
                        ->modalIcon('heroicon-m-arrow-path')
                        ->modalHeading('Restore course')
                        ->modalDescription('This action will OVERRIDE the selected course with selected backup. Are you sure you\'d like to restore the selected course? Please provide the password for the backup file.')
                        ->modalSubmitActionLabel('Restore course'),
                    Action::make('delete')
                        ->requiresConfirmation()
                        ->color(function (BackupResult $record): string {
                            $conditionsToSetDisabledColor = [
                                is_null($record->status),
                                ! $record->status,
                                is_null($record->user_id),
                                (! is_null($record->user_id) && $record->user_id !== auth()->user()->id),
                                ! is_null($record->backupStorage) ? $record->backupStorage->storage_key !== BackupStorageType::Local->value : true,
                            ];

                            if (in_array(true, $conditionsToSetDisabledColor)) {
                                return 'gray';
                            }

                            return 'danger';
                        })
                        ->disabled(function (BackupResult $record): bool {
                            $conditionsToHide = [
                                is_null($record->status),
                                ! $record->status,
                                is_null($record->user_id),
                                (! is_null($record->user_id) && $record->user_id !== auth()->user()->id),
                                ! is_null($record->backupStorage) ? $record->backupStorage->storage_key !== BackupStorageType::Local->value : true,
                            ];

                            if (in_array(true, $conditionsToHide)) {
                                return true;
                            }

                            return false;
                        })
                        ->action(function (BackupResult $record): void {
                            if ($record->in_deletion_process) {
                                Notification::make()
                                    ->title(__('Backup deletion error'))
                                    ->body(__('Backup is already in deletion process. Please wait until the process is completed.'))
                                    ->danger()
                                    ->seconds(8)
                                    ->send();

                                return;
                            }

                            if ($record->in_restore_process) {
                                Notification::make()
                                    ->title(__('Backup deletion error'))
                                    ->body(__('Backup is in restore process. Please wait until it is completed.'))
                                    ->danger()
                                    ->seconds(8)
                                    ->send();

                                return;
                            }

                            $instanceId = filament()->getTenant()->id;
                            $payload = [
                                'instance_id' => $instanceId,
                                'user_id' => auth()->user()->id,
                                // TODO: add instances current storage in V2.0
                                'storages' => [
                                    [
                                        'storage_key' => 'local',
                                        'storage_id' => $record->backupStorage ? $record->backupStorage->id : 1,
                                        'credentials' => [],
                                    ],
                                ],
                                'backups' => [
                                    [
                                        'backup_result_id' => $record->id,
                                        'storage_key' => $record->backupStorage ? $record->backupStorage->storage_key : BackupStorageType::Local->value,
                                        'storage_id' => $record->backupStorage ? $record->backupStorage->id : 1,
                                        'link' => $record->url ?? '',
                                    ],
                                ],
                            ];

                            Log::info('Manually deleting manual backup with payload: '.json_encode($payload));

                            DeleteOldBackupsJob::dispatch($instanceId, $payload, true, auth()->user());

                            Notification::make()
                                ->title(__('Backup deletion requested'))
                                ->body(__('Backup deletion for course :course was successfully requested.', ['course' => $record->course->name]))
                                ->success()
                                ->send();
                        })
                        ->icon('heroicon-o-trash')
                        ->modalIcon('heroicon-o-trash')
                        ->modalHeading(__('Delete backup'))
                        ->modalDescription(__('This action will delete the selected backup. Are you sure you\'d like to delete the selected backup?'))
                        ->modalSubmitActionLabel(__('Delete backup')),
                    // TODO: enable in V2.0 when there will be a possibility to download backups from other storages
                    /* Action::make('download')
                        ->url(fn (BackupResult $record): string => $record->url ?? '#')
                        ->openUrlInNewTab()
                        ->color(function (BackupResult $record): string {
                            $conditionsToHide = [
                                is_null($record->status),
                                ! $record->status,
                                is_null($record->url),
                                $record->backupStorage ? $record->backupStorage->storage_key !== BackupStorageType::Local->value : true,
                            ];

                            if (in_array(true, $conditionsToHide)) {
                                return 'gray';
                            }

                            return 'info';
                        })
                        ->hidden(function (BackupResult $record): bool {
                            $conditionsToHide = [
                                is_null($record->status),
                                ! $record->status,
                                is_null($record->url),
                                $record->backupStorage ? $record->backupStorage->storage_key !== BackupStorageType::Local->value : true,
                            ];

                            if (in_array(true, $conditionsToHide)) {
                                return true;
                            }

                            return false;
                        })
                        ->icon('heroicon-m-arrow-down-tray'), */
                ])
                    ->button()
                    ->label(__('Actions')),
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
