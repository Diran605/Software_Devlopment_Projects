<?php

namespace App\Providers\Filament;

use App\Models\Branch;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AppPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('app')
            ->path('app')
            ->login()
            // Ocean Blue & Teal — custom colour palette
            ->colors([
                'primary' => [
                    50  => '240, 253, 250',
                    100 => '204, 251, 241',
                    200 => '153, 246, 228',
                    300 => '94, 234, 212',
                    400 => '45, 212, 191',
                    500 => '20, 184, 166',
                    600 => '13, 148, 136',
                    700 => '15, 118, 110',
                    800 => '17, 94, 89',
                    900 => '19, 78, 74',
                    950 => '4, 47, 46',
                ],
                'gray' => [
                    50  => '248, 250, 252',
                    100 => '241, 245, 249',
                    200 => '226, 232, 240',
                    300 => '203, 213, 225',
                    400 => '148, 163, 184',
                    500 => '100, 116, 139',
                    600 => '71, 85, 105',
                    700 => '51, 65, 85',
                    800 => '30, 41, 59',
                    900 => '15, 23, 42',
                    950 => '2, 6, 23',
                ],
                'info'    => \Filament\Support\Colors\Color::Cyan,
                'success' => \Filament\Support\Colors\Color::Emerald,
                'warning' => \Filament\Support\Colors\Color::Amber,
                'danger'  => \Filament\Support\Colors\Color::Rose,
            ])
            ->brandName('IMS')
            ->defaultThemeMode(\Filament\Enums\ThemeMode::Dark)
            ->font('Inter')
            ->tenant(Branch::class, slugAttribute: 'code')
            ->discoverResources(in: app_path('Filament/App/Resources'), for: 'App\\Filament\\App\\Resources')
            ->discoverPages(in: app_path('Filament/App/Pages'), for: 'App\\Filament\\App\\Pages')
            ->discoverWidgets(in: app_path('Filament/App/Widgets'), for: 'App\\Filament\\App\\Widgets')
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
            ->authGuard('web');
    }
}
