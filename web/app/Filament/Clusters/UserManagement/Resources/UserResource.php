<?php

namespace App\Filament\Clusters\UserManagement\Resources;

use App\Enums\Role;
use App\Filament\Clusters\UserManagement;
use App\Filament\Custom\Columns as CustomFields;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Support\Enums\IconPosition;
use Filament\Tables;
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
                CustomFields\IdColumn::make('id', __('ID')),
                CustomFields\NameColumn::make('name', __('Name')),
                CustomFields\UpnColumn::make('username', __('UPN')),
                CustomFields\EmailColumn::make('email', __('Email')),
                CustomFields\AzureIdColumn::make('azure_id', __('Azure ID')),
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
                CustomFields\SortableDateTimeColumn::make('updated_at', __('Updated At')),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
            ])
            ->actions([
            ])
            ->bulkActions([
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => UserManagement\Resources\UserResource\Pages\ManageUsers::route('/'),
        ];
    }
}
