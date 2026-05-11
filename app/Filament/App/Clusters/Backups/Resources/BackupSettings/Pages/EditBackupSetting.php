<?php

namespace App\Filament\App\Clusters\Backups\Resources\BackupSettings\Pages;

use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\App\Clusters\Backups\Resources\BackupSettings\BackupSettingResource;
use Filament\Pages\Concerns\HasSubNavigation;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Support\Htmlable;

class EditBackupSetting extends EditRecord
{
    use HasSubNavigation;
    use HasTabs;

    protected static string $resource = BackupSettingResource::class;

    public static function getSubNavigationPosition(): SubNavigationPosition
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
