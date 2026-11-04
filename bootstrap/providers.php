<?php

use App\FilamentLogger\FilamentLoggerServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\EventServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\AppPanelProvider;

return [
    AppServiceProvider::class,
    EventServiceProvider::class,
    AdminPanelProvider::class,
    AppPanelProvider::class,
    FilamentLoggerServiceProvider::class,
];
