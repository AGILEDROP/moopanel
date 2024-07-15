<?php

namespace App\Filament\App\Clusters\Backups\Resources;

use App\Filament\App\Clusters\Backups;
use App\Filament\App\Clusters\Backups\Resources\BackupSettingResource\Pages;
use App\Models\BackupSetting;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\CheckboxColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class BackupSettingResource extends Resource
{
    protected static ?string $model = BackupSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Backups::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                        ->label('Delete auto backups older than'),
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
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListBackupSettings::route('/'),
            'edit' => Pages\EditBackupSetting::route('/{record}/edit'),
        ];
    }
}
