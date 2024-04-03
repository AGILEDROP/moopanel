<?php

namespace App\Filament\Clusters\Settings\Resources;

use App\Enums\Role;
use App\Filament\Clusters\Settings;
use App\Models\UniversityMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class UniversityMemberResource extends Resource
{
    protected static ?string $model = UniversityMember::class;

    protected static ?string $navigationIcon = 'fas-building-columns';

    protected static ?string $cluster = Settings::class;

    protected static ?int $navigationSort = 1;

    public static function can(string $action, ?Model $record = null): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make()
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('code')
                            ->label(__('Code'))
                            ->numeric()
                            ->required(),
                        Forms\Components\TextInput::make('acronym')
                            ->label(__('Acronym'))
                            ->required(),
                    ]),

                Forms\Components\Section::make('sis_settings')
                    ->heading(__('SIS Settings'))
                    ->description('Set SIS connection responsible for updating account type and university members relations.')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->iconSize('sm')
                    ->collapsible()
                    ->collapsed(fn (string $operation): bool => $operation !== 'create')
                    ->schema([
                        Forms\Components\TextInput::make('sis_base_url')
                            ->label(__('Base url'))
                            ->helperText('Base url for SIS endpoints.')
                            ->url()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\TextInput::make('sis_current_year')
                            ->label(__('Current school year'))
                            ->helperText('Set current school year that will be used in the API calls (example: 2023-2024).')
                            ->required(),
                        Forms\Components\TextInput::make('sis_student_years')
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
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Code'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('acronym')
                    ->label(__('Acronym'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label(__('Users'))
                    ->counts('users')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('accounts_count')
                    ->label(__('Accounts'))
                    ->counts('accounts')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('name')
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Settings\Resources\UniversityMemberResource\Pages\ManageUniversityMembers::route('/'),
        ];
    }
}
