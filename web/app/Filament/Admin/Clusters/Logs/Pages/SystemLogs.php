<?php

namespace App\Filament\Admin\Clusters\Logs\Pages;

use App\Enums\Role;
use App\Filament\Admin\Clusters\Logs;
use App\Filament\Admin\Resources\InstanceResource;
use Filament\Pages\SubNavigationPosition;
use Illuminate\Support\HtmlString;
use Saade\FilamentLaravelLog\Pages\ViewLog as BaseViewLog;

class SystemLogs extends BaseViewLog
{
    protected static bool $shouldRegisterNavigation = true;

    protected static ?string $cluster = Logs::class;

    public static function canAccess(): bool
    {
        return auth()->user()->role() === Role::MasterAdmin;
    }

    public function getBreadcrumbs(): array
    {
        return [
            InstanceResource::getUrl() => new HtmlString('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
              <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
            </svg>'),
            Logs::getUrl() => Logs::getClusterBreadcrumb(),
        ];
    }

    public function getTitle(): string
    {
        return __('System Logs');
    }

    public static function getNavigationLabel(): string
    {
        return __('System Logs');
    }

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getNavigationIcon(): string
    {
        return 'fas-server';
    }

    public function getSubNavigationPosition(): SubNavigationPosition
    {
        return SubNavigationPosition::Top;
    }
}
