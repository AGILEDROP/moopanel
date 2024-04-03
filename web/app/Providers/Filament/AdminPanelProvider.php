<?php

namespace App\Providers\Filament;

use App\Filament\Auth\Login;
use App\Filament\Resources\InstanceResource\Pages\CreateInstance;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Hydrat\TableLayoutToggle\TableLayoutTogglePlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->sidebarCollapsibleOnDesktop()
            ->renderHook('panels::auth.login.form.after', fn (): string => Blade::render('components/azure-login'))
            ->colors([
                'primary' => Color::rgb('rgb(225, 42, 38)'),
            ])
            ->maxContentWidth(true)
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->viteTheme('resources/css/app.css')
            ->pages([
                //                CreateInstance::class,
                //                Pages\Dashboard::class,
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->plugins([
                TableLayoutTogglePlugin::make()
                    ->persistLayoutInLocalStorage(false) // allow user to keep his layout preference in his local storage
                    ->shareLayoutBetweenPages(false) // allow all tables to share the layout option (requires persistLayoutInLocalStorage to be true)
                    ->displayToggleAction(false), // used to display the toggle button automatically, on the desired filament hook (defaults to table bar),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
