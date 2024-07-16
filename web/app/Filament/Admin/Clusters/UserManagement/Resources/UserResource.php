<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources;

use App\Enums\Role;
use App\Filament\Admin\Clusters\UserManagement;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\Pages;
use App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\RelationManagers\InstancesRelationManager;
use App\Filament\Custom;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $cluster = UserManagement::class;

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
                Custom\Admin\Columns\IdColumn::make('id', __('ID')),
                Custom\Admin\Columns\NameColumn::make('name', __('Name')),
                Custom\Admin\Columns\UpnColumn::make('username', __('UPN')),
                Custom\Admin\Columns\EmailColumn::make('email', __('Email')),
                Custom\Admin\Columns\AzureIdColumn::make('azure_id', __('Azure ID')),
                Tables\Columns\TextColumn::make('employee_id')
                    ->label(__('Employee ID'))
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('app_role_id')
                    ->label(__('Role'))
                    ->state(fn (User $record): string => $record->role() ? $record->role()->toReadableString() : __('No Role'))
                    ->badge()
                    ->color(fn (User $record): string => $record->role() ? $record->role()->toDisplayColor() : 'gray')
                    ->sortable(),
                Custom\Admin\Columns\SortableDateTimeColumn::make('updated_at', __('Updated At')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('app_role_id')
                    ->label(__('Role'))
                    ->options(collect(Role::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->toReadableString()];
                    })->toArray())
                    ->multiple(),
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
