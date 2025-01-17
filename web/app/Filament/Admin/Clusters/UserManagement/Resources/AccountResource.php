<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources;

use App\Enums\AccountTypes;
use App\Filament\Admin\Clusters\UserManagement;
use App\Filament\Admin\Clusters\UserManagement\Resources\AccountResource\Pages;
use App\Filament\Custom;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $cluster = UserManagement::class;

    protected static ?string $model = Account::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Custom\Admin\Columns\IdColumn::make('id', __('ID')),
                Custom\Admin\Columns\NameColumn::make('name', __('Name')),
                Custom\Admin\Columns\UpnColumn::make('username', __('UPN')),
                Custom\Admin\Columns\EmailColumn::make('email', __('Email')),
                Custom\Admin\Columns\AzureIdColumn::make('azure_id', __('Azure ID')),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn (string $state): string => AccountTypes::tryFrom($state)->toReadableString())
                    ->sortable(),
                TextColumn::make('universityMembers.name')
                    ->label(__('University Member'))
                    ->searchable()
                    ->badge()
                    ->separator(','),
                Custom\Admin\Columns\SortableDateTimeColumn::make('updated_at', __('Updated At')),
            ])
            ->filters([
                Custom\Admin\Filters\UniversityMembersFilter::make('universityMembers'),
                Tables\Filters\SelectFilter::make('type')
                    ->label(__('Type'))
                    ->options(collect(AccountTypes::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->toReadableString()];
                    })->toArray()),
            ])
            ->paginated([10, 25, 50])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageAccounts::route('/'),
        ];
    }
}
