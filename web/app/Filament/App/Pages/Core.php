<?php

namespace App\Filament\App\Pages;

use App\Livewire\App\Core\AvailableUpdatesTable;
use App\Livewire\App\Core\CurrentVersion;
use App\Livewire\App\Core\UpdateLogTable;
use Filament\Infolists\Components\Grid;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Infolist;
use Filament\Pages\Page;

class Core extends Page
{
    protected static ?string $navigationIcon = 'fas-cube';

    protected static string $view = 'filament.app.pages.core';

    public function getBreadcrumbs(): array
    {
        $breadcrumbs = (new AppDashboard)->getBreadcrumbs();
        $breadcrumbs[self::getUrl()] = self::getTitle();

        return $breadcrumbs;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Grid::make([
                    'default' => 1,
                    'lg' => 3,
                ])->schema([
                    Livewire::make(CurrentVersion::class)
                        ->columnSpan(1),
                    Livewire::make(AvailableUpdatesTable::class)
                        ->columnSpan(['default' => 1, 'lg' => 2]),
                    Livewire::make(UpdateLogTable::class)
                        ->columnSpanFull(),
                ]),
            ]);
    }
}
