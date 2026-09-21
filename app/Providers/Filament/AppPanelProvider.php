<?php

namespace App\Providers\Filament;

use App\Filament\App\Pages\ManageRestaurantSettings;
use App\Http\Controllers\App\CashierPreviewController;
use App\Http\Controllers\App\DiningTableQrController;
use App\Http\Controllers\App\GuestMenuPreviewController;
use App\Models\Restaurant;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            ->tenant(Restaurant::class, slugAttribute: 'slug', ownershipRelationship: 'restaurant')
            ->tenantMenu(false)
            ->tenantProfile(ManageRestaurantSettings::class)
            ->colors([
                'primary' => Color::Teal,
            ])
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Cashier preview')
                    ->icon(Heroicon::OutlinedDeviceTablet)
                    ->url(fn (): string => route('filament.app.cashier-preview.show', [
                        'tenant' => Filament::getTenant(),
                    ]))
                    ->openUrlInNewTab()
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.cashier-preview.show'))
                    ->sort(80),
                NavigationItem::make('Settings')
                    ->icon(Heroicon::OutlinedCog6Tooth)
                    ->url(fn (): string => ManageRestaurantSettings::getUrl())
                    ->isActiveWhen(fn (): bool => request()->routeIs('filament.app.tenant.profile'))
                    ->sort(90),
            ])
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->authenticatedTenantRoutes(function (): void {
                Route::get('dining-tables/{diningTable}/qr/png', [DiningTableQrController::class, 'png'])
                    ->name('dining-tables.qr.png');
                Route::get('dining-tables/{diningTable}/qr/pdf', [DiningTableQrController::class, 'pdf'])
                    ->name('dining-tables.qr.pdf');
                Route::get('guest-preview/tables/{diningTable}', [GuestMenuPreviewController::class, 'show'])
                    ->name('guest-preview.tables.show');
                Route::get('cashier-preview', [CashierPreviewController::class, 'show'])
                    ->name('cashier-preview.show');
            })
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
