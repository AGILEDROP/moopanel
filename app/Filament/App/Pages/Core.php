<?php

namespace App\Filament\App\Pages;

use App\Livewire\App\Core\AvailableUpdatesTable;
use App\Livewire\App\Core\CurrentVersion;
use App\Livewire\App\Core\UpdateLogTable;
use App\Models\Instance;
use App\UseCases\Syncs\SingleInstance\CoreSyncType;
use App\UseCases\Syncs\SyncTypeFactory;
use Filament\Pages\Page;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Livewire;
use Filament\Schemas\Schema;

class Core extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'fas-cube';

    protected string $view = 'filament.app.pages.core';

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = (new AppDashboard)->getBreadcrumbs();
        $breadcrumbs[self::getUrl()] = self::getTitle();

        return $breadcrumbs;
    }

    protected function getHeaderActions(): array
    {
        $syncType = SyncTypeFactory::create(CoreSyncType::TYPE, Instance::find(filament()->getTenant()->id));

        return [
            $syncType->getHeaderAction('sync', [
                'availableCoreUpdatesTableComponent',
                'coreUpdateLogTableComponent',
                'coreCurrentVersionComponent',
            ]),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])->schema([
                    Livewire::make(CurrentVersion::class)
                        ->key('current-version-component')
                        ->columnSpan(1),
                    Livewire::make(AvailableUpdatesTable::class)
                        ->key('available-updates-table')
                        ->columnSpan(['default' => 1, 'lg' => 2]),
                    Livewire::make(UpdateLogTable::class)
                        ->key('update-log-table')
                        ->columnSpanFull(),
                ]),
            ]);
    }
}
