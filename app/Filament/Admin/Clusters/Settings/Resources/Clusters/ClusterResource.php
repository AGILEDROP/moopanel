<?php

namespace App\Filament\Admin\Clusters\Settings\Resources\Clusters;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use App\Filament\Admin\Clusters\Settings\Resources\Clusters\Pages\ManageClusters;
use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Filament\Admin\Clusters\Settings\Resources\ClusterResource\Pages;
use App\Models\Cluster;
use App\Models\Instance;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ClusterResource extends Resource
{
    protected static ?string $model = Cluster::class;

    protected static string | \BackedEnum | null $navigationIcon = 'fas-layer-group';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = SettingsCluster::class;

    public static function canAccess(): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                Select::make('master_id')
                    ->options(Instance::all()->pluck('name', 'id'))
                    ->searchable()
                    ->nullable(),
                FileUpload::make('img_path')
                    ->image()
                    ->imageEditor()
                    ->imageAspectRatio('1:1')
                    ->automaticallyCropImagesToAspectRatio()
                    ->disk(config('file-uploads.disk'))
                    ->directory(config('file-uploads.'.Cluster::class))
                    ->rules(['nullable', 'mimes:jpg,jpeg,png', 'max:1024']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image')
                    ->square(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('master.name')
                    ->searchable()
                    ->sortable(),
                // todo: find fix for this alpine error!
                TextColumn::make('instances.name')
                    ->label(__('Instances'))
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList(fn () => true),
                IconColumn::make('default')
                    ->label(__('Is default'))
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label(__('Created at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('Updated at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn ($record) => $record->default === true),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([10, 25, 50])
            ->checkIfRecordIsSelectableUsing(fn (Model $record): bool => $record->default !== true)
            ->recordAction(null);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageClusters::route('/'),
        ];
    }
}
