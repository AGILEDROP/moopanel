<?php

namespace App\Filament\Admin\Clusters\Settings\Resources;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings;
use App\Filament\Admin\Clusters\Settings\Resources\ClusterResource\Pages;
use App\Models\Cluster;
use App\Models\Instance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClusterResource extends Resource
{
    protected static ?string $model = Cluster::class;

    protected static ?string $navigationIcon = 'fas-layer-group';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Settings::class;

    public static function canAccess(): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\Select::make('master_id')
                    ->options(Instance::all()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                Forms\Components\FileUpload::make('img_path')
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->disk(config('file-uploads.disk'))
                    ->directory(config('file-uploads.'.Cluster::class))
                    ->rules(['nullable', 'mimes:jpg,jpeg,png', 'max:1024']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image')
                    ->square(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('master.name')
                    ->searchable()
                    ->sortable(),
                // todo: find fix for this alpine error!
                Tables\Columns\TextColumn::make('instances.name')
                    ->label(__('Instances'))
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(fn () => true),
                Tables\Columns\IconColumn::make('default')
                    ->label(__('Is default'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Updated at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->hidden(fn ($record) => $record->default === true),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->checkIfRecordIsSelectableUsing(fn (Model $record): bool => $record->default !== true)
            ->recordAction(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageClusters::route('/'),
        ];
    }
}
