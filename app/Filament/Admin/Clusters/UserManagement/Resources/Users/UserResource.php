<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\Users;

use App\Filament\Custom\Admin\Columns\IdColumn;
use App\Filament\Custom\Admin\Columns\NameColumn;
use App\Filament\Custom\Admin\Columns\UpnColumn;
use App\Filament\Custom\Admin\Columns\EmailColumn;
use App\Filament\Custom\Admin\Columns\AzureIdColumn;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Custom\Admin\Columns\SortableDateTimeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\EditAction;
use App\Filament\Admin\Clusters\UserManagement\Resources\Users\Pages\ManageUsers;
use App\Filament\Admin\Clusters\UserManagement\Resources\Users\Pages\EditUser;
use App\Enums\Role;
use App\Filament\Admin\Clusters\UserManagement\UserManagementCluster;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages;
use App\Filament\Admin\Clusters\UserManagement\Resources\Users\RelationManagers\InstancesRelationManager;
use App\Filament\Custom;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = UserManagementCluster::class;

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 2;

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IdColumn::make('id', __('ID')),
                NameColumn::make('name', __('Name')),
                UpnColumn::make('username', __('UPN')),
                EmailColumn::make('email', __('Email')),
                AzureIdColumn::make('azure_id', __('Azure ID')),
                TextColumn::make('employee_id')
                    ->label(__('Employee ID'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('app_role_id')
                    ->label(__('Role'))
                    ->state(fn (User $record): string => $record->role() ? $record->role()->toReadableString() : __('No Role'))
                    ->badge()
                    ->color(fn (User $record): string => $record->role() ? $record->role()->toDisplayColor() : 'gray')
                    ->sortable(),
                SortableDateTimeColumn::make('updated_at', __('Updated At')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('app_role_id')
                    ->label(__('Role'))
                    ->options(collect(Role::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->toReadableString()];
                    })->toArray())
                    ->multiple(),
            ])
            ->recordActions([
                EditAction::make()
                    ->hidden(fn () => auth()->user()->role() !== Role::MasterAdmin),
            ])
            ->paginated([10, 25, 50])
            ->toolbarActions([
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
            'index' => ManageUsers::route('/'),
            'edit' => EditUser::route('edit/{record}'),
        ];
    }
}
