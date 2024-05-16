<?php

namespace App\Filament\Admin\Resources;

use App\Enums\Status;
use App\Filament\Admin\Resources\InstanceResource\Pages;
use App\Filament\Custom;
use App\Filament\Custom\Admin\Actions\Forms\CopyFieldStateAction;
use App\Models\Cluster;
use App\Models\Instance;
use App\Services\ModuleApiService;
use Filament\Forms;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn\TextColumnSize;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class InstanceResource extends Resource
{
    protected static ?string $model = Instance::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // GENERAL INFORMATION
                Grid::make()
                    ->columnSpan(1)
                    ->columns(1)
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Name'))
                            ->required(),
                        Forms\Components\TextInput::make('short_name')
                            ->label(__('Shortened form'))
                            ->live()
                            ->required(),
                        Forms\Components\Select::make('cluster_id')
                            ->label('Assign cluster')
                            ->options(Cluster::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                Forms\Components\FileUpload::make('img_path')
                    ->label(__('Choose image'))
                    ->image()
                    ->imageEditor()
                    ->imageCropAspectRatio('1:1')
                    ->disk(config('file-uploads.disk'))
                    ->directory(config('file-uploads.'.Instance::class))
                    ->rules(['nullable', 'mimes:jpg,jpeg,png', 'max:1024']),

                // CONNECTION DETAILS!
                Forms\Components\TextInput::make('url')
                    ->label(__('Base URL'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->url()
                    ->suffix(ModuleApiService::PLUGIN_PATH)
                    ->columnSpan(2),

                Forms\Components\Toggle::make('use_existing_api_key')
                    ->label(__('Use existing API Key'))
                    ->columnSpanFull()
                    ->live(),

                Forms\Components\Grid::make()
                    ->live()
                    ->disabled(fn (Forms\Get $get): bool => $get('use_existing_api_key') ?? true)
                    ->hidden(fn (Forms\Get $get): bool => $get('use_existing_api_key') ?? true)
                    ->schema([
                        Forms\Components\TextInput::make('api_key')
                            ->label(__('API Key'))
                            ->password()
                            ->revealable()
                            ->hintAction(fn () => CopyFieldStateAction::make('copy_api_key', 'Copy API Key'))
                            ->prefixActions([
                                Forms\Components\Actions\Action::make('generateApiKey')
                                    ->label(__('Generate key'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function (Forms\Set $set) {
                                        $set('api_key', Str::password(60));
                                    }),
                            ])
                            ->helperText(__('The API Key must include characters from a minimum of three out of the
                                following five categories: uppercase letters, lowercase letters, digits, non-alphanumeric
                                characters, and Unicode characters.'))
                            ->required()
                            ->minValue(40)
                            ->regex("/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/")
                            ->live(),
                        Forms\Components\DatePicker::make('key_expiration_date')
                            ->label(__('Expiration date'))
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->suffixAction(
                                Forms\Components\Actions\Action::make('clear_expiration_date')
                                    ->label(__('Clear expiration date'))
                                    ->icon('heroicon-o-x-mark')
                                    ->action(function (Forms\Set $set) {
                                        $set('key_expiration_date', null);
                                    }),
                            )
                            ->helperText(__('If this field is empty, the API key will be considered valid indefinitely.'))
                            ->minDate(now())
                            ->maxDate(now()->addYear()),
                    ])
                    ->columns(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $gridLayout = $table->getLivewire()->isGridLayout();

        return $table
            ->query(fn () => Instance::query()->with('cluster'))
            ->columns($gridLayout
                    ? static::getGridTableColumns()
                    : static::getTableColumns(),
            )
            ->contentGrid(fn (): ?array => $gridLayout ? [
                'md' => 2,
                '2xl' => 3,
            ] : null
            )
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(Status::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->toReadableString()];
                    })->toArray()),
                Tables\Filters\SelectFilter::make('cluster')
                    ->relationship('cluster', 'name')
                    ->multiple(),
                Custom\Admin\Filters\UniversityMembersFilter::make('university_member_id', 'universityMember'),
            ])
            ->filtersFormWidth(MaxWidth::Large)
            ->recordUrl(fn (Instance $record) => route('filament.app.pages.app-dashboard', ['tenant' => $record]))
            ->actions([
                Custom\Admin\Actions\Table\EditInstanceAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getGridTableColumns(): array
    {
        return [
            Tables\Columns\Layout\Stack::make([
                Tables\Columns\ImageColumn::make('image')
                    ->height('auto')
                    ->extraImgAttributes(['class' => 'rounded-md h-32 w-32'])
                    ->extraAttributes(['class' => 'mb-4']),
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->weight(FontWeight::SemiBold)
                    ->size(TextColumnSize::Large)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cluster.name')
                    ->label(__('Cluster'))
                    ->size(TextColumnSize::Medium)
                    ->sortable()
                    ->extraAttributes(['class' => 'pt-1.5 mb-3 block w-full']),
            ]),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('name')
                ->label(__('Name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('cluster.name')
                ->label(__('Cluster'))
                ->sortable(),
            Tables\Columns\TextColumn::make('plugins_count')
                ->label(__('Plugins'))
                ->counts('plugins')
                ->sortable(),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->formatStateUsing(fn (Status $state): string => $state->toReadableString())
                ->color(fn (Status $state): string => $state->toDisplayColor()),
        ];
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
            'index' => Pages\ManageInstances::route('/'),
        ];
    }
}
