<?php

namespace App\Filament\Resources;

use App\Filament\Actions\CopyFieldStateAction;
use App\Filament\Resources\InstanceResource\Pages;
use App\Models\Instance;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Spatie\Crypto\Rsa\KeyPair;

class InstanceResource extends Resource
{
    protected static ?string $model = Instance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 0;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    //                    Forms\Components\Wizard\Step::make('Moodle instance')
                    //                        ->description('Define moodle instance url')
                    //                        ->schema([
                    //                            Forms\Components\Placeholder::make('moodle_instance_text')
                    //                                ->hiddenLabel()
                    //                                ->content(new HtmlString('<h4 class="text-lg font-semibold mb-2">Instance URL</h4><p>Please enter the URL you acquired from the MDCenter plugin
                    //                                 of your chosen Moodle classroom to initiate the joining process between our application
                    //                                  and the selected Moodle instance.</p>'))
                    //                                ->columnSpanFull(),
                    //                            Forms\Components\TextInput::make('url')
                    //                                ->url()
                    //                                ->required(),
                    //                        ])->columns(),
                    Forms\Components\Wizard\Step::make('Generate keys')
                        ->description('Generate public and private key')
                        ->schema([
                            Forms\Components\Placeholder::make('generate_keys_text')
                                ->hiddenLabel()
                                ->content(new HtmlString('<h4 class="text-lg font-semibold mb-2">Generate key pair</h4>
                                <p>Create a private/public key pair for establishing secure communication between the application and the external Moodle instance plugin.</p>
                                <p>Copy the generated public key and paste it in the moodle instance module.</p>'))
                                ->columnSpanFull(),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('Generate key')
                                    ->action(function (Forms\Set $set) {
                                        //Basic!
                                        //  [$privateKey, $publicKey] = (new KeyPair())->generate();
                                        //  $set('public_key', $publicKey);
                                        //  $set('private_key', $privateKey);

                                        //Secure!
                                        // @todo: Protect with password!
                                        //@todo: save to storage! And set path to private & public key inside db!
                                        //$password =
//                                        $uuid = Str::uuid()->toString();
//                                        $folderPath = 'private/keys/'.md5(Str::random(10).time());
//                                        File::ensureDirectoryExists($folderPath);
//
//                                        $privateKeyFile = Storage::disk('local')->put($folderPath.'/'.$uuid);
//                                        $publicKeyFile = Storage::disk('local')->put($folderPath.'/'.$uuid.'.pub');

//                                        $pathToPrivateKey = storage_path('private/keys/'.$randFolder.'/'.$uuid);
//                                        $pathToPublicKey = storage_path('private/keys/'.$randFolder.'/'.$uuid.'.pub');
//                                        (new KeyPair())->generate($pathToPrivateKey, $pathToPublicKey);

                                        //https://github.com/spatie/crypto
                                    }),
                            ])->columnSpanFull(),
                            Forms\Components\Textarea::make('public_key')
                                ->rows(15)
                                ->readOnly()
                                ->hintAction(fn () => CopyFieldStateAction::make('copy_public', 'Copy public key'))
                                ->required(),
                            Forms\Components\Textarea::make('private_key')
                                ->rows(15)
                                ->readOnly()
                                ->hintAction(fn () => CopyFieldStateAction::make('copy_private', 'Copy private key'))
                                ->required(),
                        ])->columns(),
                    Forms\Components\Wizard\Step::make('Test connection')
                        ->description('Test connection with moodle instance')
                        ->schema([
                            // ...
                        ]),
                    Forms\Components\Wizard\Step::make('Review & create')
                        ->description('Review values and create a new instance')
                        ->schema([
                            // ...
                        ]),
                ])->columnSpanFull(),


                // @todo: old version
                //                Forms\Components\TextInput::make('name')
                //                    ->required(),
                //                Forms\Components\TextInput::make('url')
                //                    ->url()
                //                    ->required(),
                //                Forms\Components\Select::make('university_member_id')
                //                    ->relationship(name: 'universityMember', titleAttribute: 'name')
                //                    ->searchable()
                //                    ->required(),
                //                Forms\Components\Select::make('enterpriseApplications')
                //                    ->multiple()
                //                    ->relationship(name: 'enterpriseApplications', titleAttribute: 'name')
                //                    ->createOptionForm([
                //                        Forms\Components\TextInput::make('name')
                //                            ->required(),
                //                    ])
                //                    ->nullable(),
                //                Forms\Components\Grid::make()->schema([
                //                    Forms\Components\FileUpload::make('img_path')
                //                        ->label('Logo')
                //                        ->image()
                //                        ->imageEditor()
                //                        ->imageEditorAspectRatios([
                //                            '1:1',
                //                        ])
                //                        ->disk('public')
                //                        ->directory('logo-images')
                //                        ->columnSpan(1),
                //                ])->columns(),
            ]);
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
