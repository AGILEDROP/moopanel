<?php

namespace App\Filament\App\Clusters\Backups\Resources;

use App\Enums\BackupStorageType;
use App\Filament\App\Clusters\Backups;
use App\Filament\App\Clusters\Backups\Resources\BackupStorageResource\Pages;
use App\Models\BackupStorage;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class BackupStorageResource extends Resource
{
    protected static ?string $model = BackupStorage::class;

    protected static ?string $navigationIcon = 'heroicon-o-square-3-stack-3d';

    protected static ?string $cluster = Backups::class;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Basic Information')
                    ->schema([
                        TextInput::make('instance_id')
                            ->required()
                            ->numeric()
                            ->default(filament()->getTenant()->id)
                            ->hidden()
                            ->label('Instance ID'),
                        Toggle::make('active')
                            ->onColor('success')
                            ->offColor('danger')
                            ->default(false)
                            ->label('Active'),
                        TextInput::make('name')
                            ->required()
                            ->minLength(2)
                            ->maxLength(30)
                            ->label('Name'),
                        Select::make('storage_key')
                            ->label('Storage type')
                            ->required()
                            ->reactive()
                            ->disabled(fn (string $operation): bool => $operation !== 'create')
                            // Instance can have only one local storage
                            // if the only one local storage is somehow deleted, we can add one
                            ->disableOptionWhen(fn (string $value): bool => $value === BackupStorageType::Local->value && filament()->getTenant()->backupStorages()->where('storage_key', BackupStorageType::Local->value)->exists())
                            ->options(fn (): array => BackupStorageType::toSelectOptions()),
                    ]),
                /* Section::make('AWS S3 storage details')
                    ->visible(fn (callable $get) => $get('storage_key') !== null && $get('storage_key') === BackupStorageType::S3->value)
                    ->schema([
                        TextInput::make('url')
                            //->url()
                            ->minLength(4)
                            ->maxLength(250)
                            ->label('URL'),
                        TextInput::make('key')
                            ->password()
                            ->minLength(2)
                            ->maxLength(250)
                            ->revealable()
                            ->label('Key'),
                        TextInput::make('secret')
                            ->password()
                            ->minLength(8)
                            ->maxLength(250)
                            ->revealable()
                            ->label('Secret'),
                        TextInput::make('bucket_name')
                            ->minLength(2)
                            ->maxLength(30)
                            ->label('Bucket Name'),
                        TextInput::make('region')
                            ->minLength(2)
                            ->maxLength(30)
                            ->label('Region'),
                    ]),
                Section::make('Dropbox storage details')
                    ->visible(fn (callable $get) => $get('storage_key') !== null && $get('storage_key') === BackupStorageType::Dropbox->value)
                    ->schema([
                        TextInput::make('url')
                            //->url()
                            ->minLength(4)
                            ->maxLength(250)
                            ->label('URL'),
                        TextInput::make('key')
                            ->password()
                            ->minLength(2)
                            ->maxLength(250)
                            ->revealable()
                            ->label('Key'),
                        TextInput::make('secret')
                            ->password()
                            ->minLength(8)
                            ->maxLength(250)
                            ->revealable()
                            ->label('Secret'),
                    ]), */
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ToggleColumn::make('active')
                    ->onColor('success')
                    ->offColor('danger')
                    ->afterStateUpdated(function ($record, $state) {

                        if ($state) {
                            // uncheck all other storages in certain is selected
                            BackupStorage::where('instance_id', $record->instance_id)
                                ->where('id', '!=', $record->id)
                                ->update(['active' => 0]);
                        } else {
                            // deactivate all storages
                            BackupStorage::where('instance_id', $record->instance_id)
                                ->update(['active' => 0]);

                            // set First storage as active
                            BackupStorage::where('instance_id', $record->instance_id)
                                ->orderBy('id', 'asc')
                                ->first()
                                ->update(['active' => 1]);
                        }
                    })
                    ->label('Active'),
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('storage_key')
                    ->badge()
                    ->icon('heroicon-o-circle-stack')
                    ->color(fn (string $state): string => BackupStorageType::keyToColor($state))
                    ->formatStateUsing(fn (string $state): string => BackupStorageType::keyToReadableString($state))
                    ->label('Type'),
            ])
            ->filters([
                //
            ])
            ->paginated([10, 25, 50])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ListBackupStorages::route('/'),
            'create' => Pages\CreateBackupStorage::route('/create'),
            'edit' => Pages\EditBackupStorage::route('/{record}/edit'),
        ];
    }
}
