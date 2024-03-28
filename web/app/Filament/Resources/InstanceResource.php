<?php

namespace App\Filament\Resources;

use App\Enums\Status;
use App\Filament\Resources\InstanceResource\Pages;
use App\Models\Instance;
use App\Tables\Columns\LogoImageColumn;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Tables;
use Filament\Tables\Table;

class InstanceResource extends Resource
{
    protected static ?string $model = Instance::class;

    protected static ?string $label = 'Moodle Instance';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 0;

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Instance::query()
                ->with('tags')
            )
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    LogoImageColumn::make('logo'),
                    Tables\Columns\TextColumn::make('site_name')
                        ->label(__('Site name'))
                        ->weight(FontWeight::Bold)
                        ->sortable()
                        ->searchable()
                        ->extraAttributes(['class' => 'pt-3']),
                    Tables\Columns\TextColumn::make('theme')
                        ->label(__('Theme'))
                        ->extraAttributes(['class' => 'pt-1.5 block w-full']),
                    Tables\Columns\TextColumn::make('version')
                        ->label(__('Version'))
                        ->extraAttributes(['class' => 'pt-1.5 block w-full']),
                    Tables\Columns\TextColumn::make('status')
                        ->label(__('Status'))
                        ->badge()
                        ->formatStateUsing(fn (string $state): string => Status::tryFrom($state)->toReadableString())
                        ->color(fn (string $state): string => Status::tryFrom($state)->toDisplayColor())
                        ->extraAttributes(['class' => 'mb-3 pt-1.5 block w-full']),
                ]),
            ])
            ->contentGrid([
                'md' => 2,
                '2xl' => 3,
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('university_member_id')
                    ->label(__('University member'))
                    ->multiple()
                    ->relationship('universityMember', 'name')
                    ->searchable(),
                // @todo: update to use and & or operations.
                Tables\Filters\SelectFilter::make('tags')
                    ->label(__('Tags'))
                    ->multiple()
                    ->relationship('tags', 'name')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('visit_site')
                        ->label(__('Visit site'))
                        ->color('gray')
                        ->icon('heroicon-o-link')
                        ->url(fn (Instance $record): string => stripUrlPath($record->url))
                        ->openUrlInNewTab(),
                ])
                    ->link()
                    ->label('Actions'),

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
