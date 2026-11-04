<?php

namespace App\Filament\Admin\Resources\Instances;

use App\Enums\Status;
use App\Filament\Admin\Resources\Instances\Pages\ManageInstances;
use App\Filament\Custom;
use App\Filament\Custom\Admin\Actions\Forms\CopyFieldStateAction;
use App\Filament\Custom\Admin\Actions\Table\EditInstanceAction;
use App\Filament\Custom\Admin\Filters\UniversityMembersFilter;
use App\Models\Cluster;
use App\Models\Instance;
use App\Services\ModuleApiService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class InstanceResource extends Resource
{
    protected static ?string $model = Instance::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationLabel = 'Home';

    protected static ?int $navigationSort = 0;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // GENERAL INFORMATION
                Grid::make()
                    ->columnSpan(1)
                    ->columns(1)
                    ->schema([
                        TextInput::make('name')
                            ->label(__('Name'))
                            ->required(),
                        TextInput::make('short_name')
                            ->label(__('Shortened form'))
                            ->live()
                            ->required(),
                        Select::make('cluster_id')
                            ->label('Assign cluster')
                            ->options(Cluster::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                    ]),
                FileUpload::make('img_path')
                    ->label(__('Choose image'))
                    ->image()
                    ->imageEditor()
                    ->imageAspectRatio('1:1')
                    ->automaticallyCropImagesToAspectRatio()
                    ->disk(config('file-uploads.disk'))
                    ->directory(config('file-uploads.'.Instance::class))
                    ->rules(['nullable', 'mimes:jpg,jpeg,png', 'max:1024']),

                // CONNECTION DETAILS!
                TextInput::make('url')
                    ->label(__('Base URL'))
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->url()
                    ->suffix(ModuleApiService::PLUGIN_PATH)
                    ->columnSpan(2),

                Toggle::make('use_existing_api_key')
                    ->label(__('Use existing API Key'))
                    ->columnSpanFull()
                    ->live(),

                Grid::make()
                    ->live()
                    ->disabled(fn (Get $get): bool => $get('use_existing_api_key') ?? true)
                    ->hidden(fn (Get $get): bool => $get('use_existing_api_key') ?? true)
                    ->schema([
                        TextInput::make('api_key')
                            ->label(__('API Key'))
                            ->password()
                            ->revealable()
                            ->hintAction(fn () => CopyFieldStateAction::make('copy_api_key', 'Copy API Key'))
                            ->prefixActions([
                                Action::make('generateApiKey')
                                    ->label(__('Generate key'))
                                    ->icon('heroicon-o-arrow-path')
                                    ->action(function (Set $set) {
                                        $set('api_key', Str::password(60));
                                    }),
                            ])
                            ->helperText(new HtmlString(__(
                                ':start The API Key must include characters from a minimum of three out of the following five categories: 
                                uppercase letters, lowercase letters, digits, non-alphanumeric characters, and Unicode characters. :end',
                                [
                                    'start' => '<p class="text-dark dark:text-white">',
                                    'end' => '</p>',
                                ]
                            )))
                            ->required()
                            ->minValue(40)
                            ->regex("/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%]).*$/")
                            ->live(),
                        DatePicker::make('key_expiration_date')
                            ->label(__('Expiration date'))
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->suffixAction(
                                Action::make('clear_expiration_date')
                                    ->label(__('Clear expiration date'))
                                    ->icon('heroicon-o-x-mark')
                                    ->action(function (Set $set) {
                                        $set('key_expiration_date', null);
                                    }),
                            )
                            ->helperText(new HtmlString(__(
                                ':start If this field is empty, the API key will be considered valid indefinitely. :end',
                                [
                                    'start' => '<p class="text-dark dark:text-white">',
                                    'end' => '</p>',
                                ]
                            )))
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
            ->columns(
                $gridLayout
                    ? static::getGridTableColumns()
                    : static::getTableColumns(),
            )
            ->contentGrid(
                fn (): ?array => $gridLayout ? [
                    'md' => 2,
                    '2xl' => 3,
                ] : null
            )
            ->filters([
                SelectFilter::make('status')
                    ->label(__('Status'))
                    ->options(collect(Status::cases())->mapWithKeys(function ($case) {
                        return [$case->value => $case->toReadableString()];
                    })->toArray()),
                SelectFilter::make('cluster')
                    ->relationship('cluster', 'name')
                    ->multiple(),
                UniversityMembersFilter::make('university_member_id', 'universityMember'),
            ])
            ->filtersFormWidth(Width::Large)
            ->recordUrl(fn (Instance $record) => route('filament.app.pages.app-dashboard', ['tenant' => $record]))
            ->recordActions([
                EditInstanceAction::make(),
            ])
            ->paginated([10, 25, 50])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getGridTableColumns(): array
    {
        return [
            Stack::make([
                ImageColumn::make('image')
                    ->height('auto')
                    ->extraImgAttributes(['class' => 'rounded-md h-32 w-32'])
                    ->extraAttributes(['class' => 'mb-4']),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->weight(FontWeight::SemiBold)
                    ->size(TextSize::Large)
                    ->sortable()
                    ->searchable(),
                TextColumn::make('cluster.name')
                    ->label(__('Cluster'))
                    ->size(TextSize::Medium)
                    ->sortable()
                    ->extraAttributes(['class' => 'pt-1.5 mb-3 block w-full']),
            ]),
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            TextColumn::make('name')
                ->label(__('Name'))
                ->sortable()
                ->searchable(),
            TextColumn::make('cluster.name')
                ->label(__('Cluster'))
                ->sortable(),
            TextColumn::make('plugins_count')
                ->label(__('Plugins'))
                ->counts('plugins')
                ->sortable(),
            TextColumn::make('status')
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
            'index' => ManageInstances::route('/'),
        ];
    }
}
