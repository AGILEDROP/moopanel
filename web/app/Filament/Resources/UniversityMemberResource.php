<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UniversityMemberResource\Pages;
use App\Models\UniversityMember;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UniversityMemberResource extends Resource
{
    protected static ?string $model = UniversityMember::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Grid::make([
                    'default' => 1,
                    'sm' => 2,
                ])->schema([
                    Forms\Components\TextInput::make('name')
                        ->columnSpanFull()
                        ->required(),
                    Forms\Components\TextInput::make('code')
                        ->numeric()
                        ->required(),
                    Forms\Components\TextInput::make('acronym')
                        ->required(),
                ]),

                Forms\Components\Fieldset::make('SIS Settings')
                    ->schema([
                        Forms\Components\TextInput::make('sis_base_url')
                            ->label('Base url')
                            ->helperText('Base url for SIS endpoints.')
                            ->url()
                            ->required()
                            ->translateLabel(),
                        Forms\Components\TextInput::make('sis_current_year')
                            ->label('Current school year')
                            ->helperText('Set current school year that will be used in the API calls (example: 2023-2024).')
                            ->required()
                            ->translateLabel(),
                        Forms\Components\TextInput::make('sis_student_years')
                            ->label('Max student years')
                            ->helperText('It tells us how many recent school years we must consider when retrieving student data.
                            By default, it is set to 1, which means that only data from the current school year is used.')
                            ->integer()
                            ->minValue(1)
                            ->maxValue(8)
                            ->default(1)
                            ->required()
                            ->translateLabel(),
                    ])->translateLabel(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->sortable()
                    ->searchable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('acronym')
                    ->sortable()
                    ->searchable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('name')
                    ->sortable()
                    ->searchable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users count')
                    ->counts('users')
                    ->sortable()
                    ->translateLabel(),
                Tables\Columns\TextColumn::make('accounts_count')
                    ->label('Accounts count')
                    ->counts('accounts')
                    ->sortable()
                    ->translateLabel(),
            ])
            ->defaultSort('acronym', 'asc')
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
            'index' => Pages\ListUniversityMembers::route('/'),
            'create' => Pages\CreateUniversityMember::route('/create'),
            'edit' => Pages\EditUniversityMember::route('/{record}/edit'),
        ];
    }
}
