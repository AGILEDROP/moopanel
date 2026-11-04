<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupSettings;

use App\Filament\App\Clusters\Backups\BackupsCluster;
use App\Filament\App\Clusters\Backups\Resources\BackupSettings\Pages\EditBackupSetting;
use App\Filament\App\Clusters\Backups\Resources\BackupSettings\Pages\ListBackupSettings;
use App\Models\BackupSetting;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;

class BackupSettingResource extends Resource
{
    protected static ?string $model = BackupSetting::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = BackupsCluster::class;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'sm' => 2,
                ])->schema([
                    Section::make()
                        ->schema([
                            Fieldset::make(__('Backup Auto Generation'))
                                ->schema([
                                    Toggle::make('auto_backups_enabled')
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->inline(false)
                                        ->label('Auto Backups Enabled'),
                                    TextInput::make('backup_interval')
                                        ->label('Backup Interval')
                                        ->prefixIcon('heroicon-o-circle-stack')
                                        ->prefixIconColor('success')
                                        ->suffix(__('Hours'))
                                        // up to years 17530hrs = 2years
                                        ->rules('min:1|max:18000')
                                        ->required()
                                        ->numeric(),
                                ])
                                ->columns(2),
                        ]),
                    Section::make()
                        ->schema([
                            Fieldset::make(__('Backup Auto Deletion'))
                                ->schema([
                                    Toggle::make('auto_deletion_enabled')
                                        ->onColor('success')
                                        ->offColor('danger')
                                        ->inline(false)
                                        ->label('Auto Deletions Enabled'),
                                    TextInput::make('backup_deletion_interval')
                                        ->numeric()
                                        ->prefixIcon('heroicon-o-x-circle')
                                        ->rules('min:1|max:365')
                                        ->prefixIconColor('danger')
                                        ->required()
                                        ->suffix(__('Days'))
                                        ->label('Delete auto backups older than')
                                        ->helperText(new HtmlString(__(
                                            ':start Note: System will always keep the most recent auto-backup, despite it might be suitable to delete :end',
                                            [
                                                'start' => '<p class="text-dark dark:text-white">',
                                                'end' => '</p>',
                                            ]
                                        ))),
                                ])
                                ->columns(2),
                        ]),
                    Section::make()
                        ->schema([
                            Fieldset::make(__('Last active'))
                                ->schema([
                                    DateTimePicker::make('backup_last_run')
                                        ->readOnly()
                                        ->prefixIcon('heroicon-o-circle-stack')
                                        ->prefixIconColor('success')
                                        ->label('Backup Last Run'),
                                    DateTimePicker::make('deletion_last_run')
                                        ->readOnly()
                                        ->prefixIcon('heroicon-o-x-circle')
                                        ->prefixIconColor('danger')
                                        ->label('Deletion Last Run'),
                                ])
                                ->columns(2),
                        ]),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                CheckboxColumn::make('auto_backups_enabled')
                    ->label(__('Auto backups')),
                TextColumn::make('backup_interval')
                    ->label(__('Backup interval')),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->paginated([10, 25, 50])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
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
            'index' => ListBackupSettings::route('/'),
            'edit' => EditBackupSetting::route('/{record}/edit'),
        ];
    }
}
