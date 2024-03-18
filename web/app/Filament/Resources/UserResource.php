<?php

namespace App\Filament\Resources;

use App\Enums\Role;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'fas-user-shield';

    protected static ?string $navigationGroup = 'User management';

    protected static ?int $navigationSort = 998;

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('azure_id')
                    ->numeric()
                    ->label('Azure ID')
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->copyable()
                    ->searchable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('username')
                    ->label('UPN')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->copyable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('app_role_id')
                    ->state(fn (User $record): string => $record->role() ? $record->role()->name : __('No Role'))
                    ->sortable()
                    ->label('Role')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Updated At')
                    ->translateLabel(),
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
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
