<?php

namespace App\Filament\Admin\Clusters\Settings\Resources\UniversityMembers;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Settings\Resources\UniversityMembers\Pages\ManageUniversityMembers;
use App\Filament\Admin\Clusters\Settings\SettingsCluster;
use App\Models\UniversityMember;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UniversityMemberResource extends Resource
{
    protected static ?string $model = UniversityMember::class;

    protected static string|\BackedEnum|null $navigationIcon = 'fas-building-columns';

    protected static ?string $cluster = SettingsCluster::class;

    protected static ?int $navigationSort = 2;

    public static function can(string|\UnitEnum $action, ?Model $record = null): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make()
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('code')
                            ->label(__('Code'))
                            ->numeric()
                            ->required(),
                        TextInput::make('acronym')
                            ->label(__('Acronym'))
                            ->required(),
                    ]),

                Section::make('sis_settings')
                    ->heading(__('SIS Settings'))
                    ->description('Set SIS connection responsible for updating account type and university members relations.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->iconSize('sm')
                    ->collapsible()
                    ->collapsed(fn (string $operation): bool => $operation !== 'create')
                    ->schema([
                        TextInput::make('sis_base_url')
                            ->label(__('Base url'))
                            ->helperText('Base url for SIS endpoints.')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        TextInput::make('sis_current_year')
                            ->label(__('Current school year'))
                            ->helperText('Set current school year that will be used in the API calls (example: 2023-2024).')
                            ->required(),
                        TextInput::make('sis_student_years')
                            ->label(__('Max student years'))
                            ->helperText('It tells us how many recent school years we must consider when retrieving student data.
                            By default, it is set to 1, which means that only data from the current school year is used.')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(8)
                            ->default(1)
                            ->required(),
                    ])->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                    ->label(__('Code'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('acronym')
                    ->label(__('Acronym'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                TextColumn::make('users_count')
                    ->label(__('Users'))
                    ->counts('users')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('accounts_count')
                    ->label(__('Accounts'))
                    ->counts('accounts')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->paginated([10, 25, 50])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUniversityMembers::route('/'),
        ];
    }
}
