<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\Accounts;

use App\Enums\AccountTypes;
use App\Filament\Admin\Clusters\UserManagement\Resources\Accounts\Pages\ManageAccounts;
use App\Filament\Admin\Clusters\UserManagement\UserManagementCluster;
use App\Filament\Custom;
use App\Filament\Custom\Admin\Columns\AzureIdColumn;
use App\Filament\Custom\Admin\Columns\EmailColumn;
use App\Filament\Custom\Admin\Columns\IdColumn;
use App\Filament\Custom\Admin\Columns\NameColumn;
use App\Filament\Custom\Admin\Columns\SortableDateTimeColumn;
use App\Filament\Custom\Admin\Columns\UpnColumn;
use App\Filament\Custom\Admin\Filters\UniversityMembersFilter;
use App\Models\Account;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AccountResource extends Resource
{
    protected static ?string $cluster = UserManagementCluster::class;

    protected static ?string $model = Account::class;

    protected static ?string $recordTitleAttribute = 'name';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                IdColumn::make('id', __('ID')),
                NameColumn::make('name', __('Name')),
                UpnColumn::make('username', __('UPN')),
                EmailColumn::make('email', __('Email')),
                AzureIdColumn::make('azure_id', __('Azure ID')),
                TextColumn::make('type')
                    ->label(__('Type'))
                    ->formatStateUsing(fn (string $state): string => AccountTypes::tryFrom($state)->toReadableString())
                    ->sortable(),
                TextColumn::make('universityMembers.name')
                    ->label(__('University Member'))
                    ->searchable()
                    ->badge()
                    ->separator(','),
                SortableDateTimeColumn::make('updated_at', __('Updated At')),
            ])
            ->filters([
                UniversityMembersFilter::make('universityMembers'),
                SelectFilter::make('type')
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
            'index' => ManageAccounts::route('/'),
        ];
    }
}
