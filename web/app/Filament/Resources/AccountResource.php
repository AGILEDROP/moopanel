<?php

namespace App\Filament\Resources;

use App\Enums\AccountTypes;
use App\Filament\Resources\AccountResource\Pages;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
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
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->label('ID'),
                Tables\Columns\TextColumn::make('azure_id')
                    ->numeric()
                    ->label('Azure ID')
                    ->copyable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->copyable()
                    ->sortable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('username')
                    ->label('UPN')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->formatStateUsing(fn (string $state): string => AccountTypes::tryFrom($state)->toReadableString())
                    ->sortable()
                    ->translateLabel(),
                TextColumn::make('universityMembers.name')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->separator(',')
                    ->label('University Member')
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Updated At')
                    ->translateLabel(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('universityMembers')
                    ->label('University member')
                    ->translateLabel()
                    ->multiple()
                    ->relationship('universityMembers', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('type')
                    ->options(collect(AccountTypes::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->toReadableString()];
                    })->toArray()),
            ])
            ->defaultSort('updated_at', 'desc')
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
