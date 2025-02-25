<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupResultResource\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupResultResource;
use Filament\Pages\Concerns\HasSubNavigation;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListBackupResults extends ListRecords
{
    use HasSubNavigation;

    protected static string $resource = BackupResultResource::class;

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

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
