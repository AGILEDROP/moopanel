<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $model = Account::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationIcon = 'fas-users';

    protected static ?string $navigationGroup = 'User management';

    protected static ?int $navigationSort = 999;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->numeric()->label('ID'),
                Tables\Columns\TextColumn::make('azure_id')->numeric()->label('Azure ID'),
                Tables\Columns\TextColumn::make('name')->searchable()->translateLabel(),
                Tables\Columns\TextColumn::make('username')->label('UPN')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                // Tables\Columns\TextColumn::make('universityMember.company_name')->sortable()->label('University Member')->translateLabel(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable()->label('Updated At')->translateLabel(),
            ])
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
            'index' => Pages\ManageAccounts::route('/'),
        ];
    }
}
