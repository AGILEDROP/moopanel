<?php

namespace App\Providers;

use App\SCIM\AzureSCIMConfig;
use ArieTimmerman\Laravel\SCIMServer\SCIMConfig;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(SCIMConfig::class, AzureSCIMConfig::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! app()->environment('local')) {
            URL::forceScheme('https');
        }
    }
}
