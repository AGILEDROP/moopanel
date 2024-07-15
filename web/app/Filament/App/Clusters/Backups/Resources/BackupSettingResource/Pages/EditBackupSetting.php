<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupSettingResource\Pages;

use App\Filament\App\Clusters\Backups\Resources\BackupSettingResource;
use Filament\Pages\Concerns\HasSubNavigation;
use Filament\Pages\SubNavigationPosition;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditBackupSetting extends EditRecord
{
    use HasSubNavigation;
    use HasTabs;

    protected static string $resource = BackupSettingResource::class;

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }

    /**
     * @return array<NavigationItem | NavigationGroup>
     */
    public function getSubNavigation(): array
    {
        if (filled($cluster = static::getCluster())) {
            return $this->generateNavigationItems($cluster::getClusteredComponents());
        }

        return [];
    }

    public function getTitle(): string|Htmlable
    {
        return __('Backup settings');
    }
}
