<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
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
            ->brandName('WebGIS Kuliner Nusantara')
            ->favicon(asset('favicon.ico'))
            // Autentikasi
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->authGuard('web')
            // Tema light (tanpa dark mode), aksen teal seperti geoportal
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#0aa5b8'),
                'gray' => Color::Slate,
                'info' => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
            ])
            ->font('Inter')
            ->sidebarWidth('16rem')
            ->sidebarCollapsibleOnDesktop()
            // Navigasi antar menu tanpa reload halaman penuh (wire:navigate)
            ->spa()
            ->globalSearch()
            ->globalSearchKeyBindings(['mod+k'])
            ->databaseTransactions()
            ->navigationGroups([
                'Konten',
                'Wilayah',
                'Media',
                'Pengaturan',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            // App\Filament\Pages\Dashboard ikut ter-discover dari folder ini
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            // Leaflet untuk widget peta di dashboard
            ->renderHook(PanelsRenderHook::HEAD_END, fn (): string => view('filament.hooks.leaflet-assets')->render())
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn (): string => view('filament.hooks.theme')->render())
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
