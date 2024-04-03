<?php

namespace App\Filament\Resources\InstanceResource\Widgets;

use App\Services\ModuleApiService;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OnlineUsersWidget extends BaseWidget
{
    use InteractsWithRecord;

    protected static ?string $pollingInterval = '60s';

    protected function getStats(): array
    {
        $onlineUsersCount = (new ModuleApiService)->getOnlineUsersCount($this->getRecord());

        return [
            Stat::make('online_users', $onlineUsersCount)
                ->label(__('Online users'))
                ->icon('fas-user-clock'),
        ];
    }
}
