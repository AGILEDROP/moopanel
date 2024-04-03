<?php

namespace App\Filament\Resources;

use App\Enums\Status;
use App\Filament\Custom;
use App\Filament\Resources\InstanceResource\Pages;
use App\Filament\Resources\InstanceResource\RelationManagers\PluginsRelationManager;
use App\Models\Instance;
use App\Tables\Columns\LogoImageColumn;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Table;

class InstanceResource extends Resource
{
    protected static ?string $model = Instance::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 0;

    protected static ?string $activeNavigationIcon = 'heroicon-o-home';

    public static function table(Table $table): Table
    {
        return $table
            ->query(fn () => Instance::query()
                ->with('tags')
            )
            ->columns(
                $table->getLivewire()->isGridLayout()
                    ? static::getGridTableColumns()
                    : static::getTableColumns(),
            )
            ->contentGrid([
                'md' => 2,
                '2xl' => 4,
            ])
            ->filters([
                Custom\Filters\UniversityMembersFilter::make('university_member_id', 'universityMember'),
                Custom\Filters\TagsFilter::make(),
            ])
            ->filtersFormWidth(MaxWidth::Large)
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

    public static function getGridTableColumns(): array
    {
        return [
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
        ];
    }

    public static function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('site_name')
                ->label(__('Site name'))
                ->sortable()
                ->searchable(),
            Tables\Columns\TextColumn::make('theme')
                ->label(__('Theme')),
            Tables\Columns\TextColumn::make('version')
                ->label(__('Version')),
            Tables\Columns\TextColumn::make('status')
                ->label(__('Status'))
                ->badge()
                ->formatStateUsing(fn (string $state): string => Status::tryFrom($state)->toReadableString())
                ->color(fn (string $state): string => Status::tryFrom($state)->toDisplayColor()),
        ];
    }

    public static function getRelations(): array
    {
        return [
            PluginsRelationManager::class,
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
