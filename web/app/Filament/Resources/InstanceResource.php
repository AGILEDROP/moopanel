<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InstanceResource\Pages;
use App\Models\Instance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class InstanceResource extends Resource
{
    protected static ?string $model = Instance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('url')
                    ->url()
                    ->required(),
                Forms\Components\Select::make('university_member_id')
                    ->relationship(name: 'universityMember', titleAttribute: 'name')
                    ->searchable()
                    ->required(),
                Forms\Components\Select::make('enterpriseApplications')
                    ->multiple()
                    ->relationship(name: 'enterpriseApplications', titleAttribute: 'name')
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')
                            ->required(),
                    ])
                    ->nullable(),
                Forms\Components\Grid::make()->schema([
                    Forms\Components\FileUpload::make('img_path')
                        ->label('Logo')
                        ->image()
                        ->imageEditor()
                        ->imageEditorAspectRatios([
                            '1:1',
                        ])
                        ->disk('public')
                        ->directory('logo-images')
                        ->columnSpan(1),
                ])->columns(),
            ])->columns();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Instance::query()
                ->with('enterpriseApplications')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('img_path')
                        ->height(75),
                    Tables\Columns\TextColumn::make('name')
                        ->weight(FontWeight::Bold)
                        ->sortable()
                        ->searchable()
                        ->extraAttributes(['class' => 'pt-2']),
                    Tables\Columns\TextColumn::make('url')
                        ->url(fn (Instance $record): string => $record->url)
                        ->openUrlInNewTab()
                        ->extraAttributes(['class' => 'pt-1 ps-2 pe-4 block w-full']),
                    Tables\Columns\TextColumn::make('enterpriseApplications.name')
                        ->extraAttributes(['class' => 'pt-2 ps-2 pe-4 block w-full'])
                        ->badge()
                        ->separator(','),
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                '2xl' => 3,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('university_member_id')
                    ->label('University member')
                    ->translateLabel()
                    ->multiple()
                    ->relationship('universityMember', 'name')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('enterpriseApplications')
                    ->multiple()
                    ->relationship('enterpriseApplications', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                // @todo: prepared for triggering commands through modal window.
                //  Tables\Actions\ActionGroup::make([
                //
                //  ])->icon('heroicon-o-command-line')
                //      ->link()
                //      ->label('Run Command'),
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
            'index' => Pages\ListInstances::route('/'),
            'create' => Pages\CreateInstance::route('/create'),
            'view' => Pages\ViewInstance::route('/{record}'),
            'edit' => Pages\EditInstance::route('/{record}/edit'),
        ];
    }
}
