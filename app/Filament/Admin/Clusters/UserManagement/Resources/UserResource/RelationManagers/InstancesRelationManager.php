<?php

namespace App\Filament\Admin\Clusters\UserManagement\Resources\UserResource\RelationManagers;

use App\Models\Instance;
use App\Models\Scopes\InstanceScope;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InstancesRelationManager extends RelationManager
{
    protected static string $relationship = 'instances';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->withoutGlobalScopes([
                InstanceScope::class,
            ]))
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Name'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('cluster.name')
                    ->label(__('Cluster'))
                    ->searchable()
                    ->sortable(),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->recordSelectOptionsQuery(function () {
                        // custom query to avoid "select distinct" query, that is not supported by psql for json() column
                        return Instance::withoutGlobalScope(InstanceScope::class)
                            ->select('instances.*')
                            ->leftJoin('instance_user', 'instances.id', '=', 'instance_user.instance_id');
                    })
                    ->action(function (array $data, Tables\Actions\AttachAction $action): void {
                        $this->ownerRecord->instances()->attach($data['recordId']);
                        $action->sendSuccessNotification();
                    }),
            ])
            ->actions([
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
