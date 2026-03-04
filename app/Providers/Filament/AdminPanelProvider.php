<?php

namespace App\Providers\Filament;

use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->homeUrl('/admin/payment-requests')
            ->colors([
                'primary' => [
                    50 => '#EFE9DD',
                    100 => '#E8DFD0',
                    200 => '#D5C9B3',
                    300 => '#C5A059',
                    400 => '#B08A3E',
                    500 => '#9A7530',
                    600 => '#7D5F27',
                    700 => '#614A1F',
                    800 => '#453516',
                    900 => '#3D3870',
                    950 => '#191731',
                ],
                'purple' => Color::Purple,
            ])
            ->font('Open Sans')
            ->navigationGroups([
                NavigationGroup::make('Operaciones'),
                NavigationGroup::make('Organización')->collapsible(),
                NavigationGroup::make('Catálogos')->collapsible(),
                NavigationGroup::make('Accesos')->collapsible(),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([])
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
            ])
            ->databaseNotifications()
            ->plugins([
                FilamentShieldPlugin::make(),
            ]);
    }
}
