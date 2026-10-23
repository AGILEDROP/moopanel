<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupResults\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupResults\BackupResultResource;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListBackupResults extends ListRecords
{
    protected static string $resource = BackupResultResource::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All backups'),
            'manual' => Tab::make('Manual backups')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNotNull('user_id')),
            'automatic' => Tab::make('Automatic backups')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('user_id')),
        ];
    }
}
