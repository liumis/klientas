<?php

namespace App\Providers\Filament;

use App\Filament\Resources\ClaimResource;
use App\Support\SharePointFileUrl;
use Filament\Http\Middleware\Authenticate;
use Filament\Navigation\NavigationItem;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class SecurePanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('secure')
            ->path('secure')
            ->homeUrl(fn (): string => ClaimResource::getUrl(panel: 'secure'))
            ->login()
            ->passwordReset()
            ->brandName('Sit&Go')
            ->brandLogo(asset('images/sitandgo-logo.png'))
            ->brandLogoHeight('2.25rem')
            ->favicon(asset('images/brand/favicon.png'))
            ->darkMode(false)
            ->colors([
                'primary' => Color::hex('#1f3446'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([

            ])
            ->navigationItems([
                NavigationItem::make('sharepoint-excel')
                    ->label('Excel data file')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (): string => SharePointFileUrl::fromSettings() ?? '#')
                    ->openUrlInNewTab()
                    ->sort(41)
                    ->visible(fn (): bool => SharePointFileUrl::fromSettings() !== null),
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
