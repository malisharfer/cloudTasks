<?php

namespace App\Providers;

use App\Filament\Auth\Login;
use App\Filament\Widgets\CalendarWidget;
use App\Http\Middleware\SetLocale;
use App\Resources\ProfileResource\Pages\ListProfiles;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Saade\FilamentFullCalendar\FilamentFullCalendarPlugin;

class FilamentServiceProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->default()
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->discoverResources(in: app_path('Resources'), for: 'App\\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->login(Login::class)
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
                SetLocale::class,

            ])
            ->navigationGroups([])
            ->passwordReset()
            ->path('/')
            ->plugins([
                FilamentFullCalendarPlugin::make()
                    ->selectable()
                    ->editable()
                    ->timezone(config('app.timezone'))
                    ->locale(config('app.locale'))
                    ->plugins(['dayGrid', 'timeGrid'])
                    ->config([]),
            ])
            ->pages([
                ListProfiles::class,
            ])
            ->sidebarWidth('17rem')
            ->maxContentWidth('full')
            ->viteTheme('resources/css/app.css')
            ->widgets([
                CalendarWidget::class,
            ]);
    }

    public function register(): void
    {
        parent::register();
    }

    public function boot()
    {
        Filament::serving(function () {
            Filament::registerNavigationGroups([
                NavigationGroup::make()
                    ->label(fn (): string => __('Constraints'))
                    ->icon('heroicon-o-calendar-days'),
                NavigationGroup::make()
                    ->label(fn (): string => __('Shifts'))
                    ->icon('heroicon-o-calendar'),
            ]);
        });
    }
}
