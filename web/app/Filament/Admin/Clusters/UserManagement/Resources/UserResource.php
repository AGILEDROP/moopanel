<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources;

use App\Enums\Role;
use App\Filament\Admin\Clusters\UserManagement;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\RelationManagers\InstancesRelationManager;
use App\Filament\Admin\Custom;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = UserManagement::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'fas-user-shield';

    protected static ?int $navigationSort = 2;

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Custom\Columns\IdColumn::make('id', __('ID')),
                Custom\Columns\NameColumn::make('name', __('Name')),
                Custom\Columns\UpnColumn::make('username', __('UPN')),
                Custom\Columns\EmailColumn::make('email', __('Email')),
                Custom\Columns\AzureIdColumn::make('azure_id', __('Azure ID')),
                Tables\Columns\TextColumn::make('employee_id')
                    ->label(__('Employee ID'))
                    ->copyable()
                    ->icon('heroicon-m-document-duplicate')
                    ->iconPosition(IconPosition::After)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('app_role_id')
                    ->label(__('Role'))
                    ->state(fn (User $record): string => $record->role() ? $record->role()->toReadableString() : __('No Role'))
                    ->badge()
                    ->color(fn (User $record): string => $record->role() ? $record->role()->toDisplayColor() : 'gray')
                    ->sortable(),
                Custom\Columns\SortableDateTimeColumn::make('updated_at', __('Updated At')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
            ])
            ->actions([
                EditAction::make()
                    ->hidden(fn () => auth()->user()->role() !== Role::MasterAdmin),
            ])
            ->bulkActions([
            ]);
    }

    public static function getRelations(): array
    {
        return [
            InstancesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageUsers::route('/'),
            'edit' => Pages\EditUser::route('edit/{record}'),
        ];
    }
}
