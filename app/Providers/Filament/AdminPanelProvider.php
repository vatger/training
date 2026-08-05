<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
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
            ->defaultThemeMode(ThemeMode::System)
            ->brandName('vatger Training System')
            ->favicon(asset('favicon.svg'))
            ->brandLogo(asset('images/brand/logo-training-on-light.svg'))
            ->darkModeBrandLogo(asset('images/brand/logo-training-on-dark.svg'))
            ->brandLogoHeight('2.25rem')
            ->colors([
                'primary' => [
                    50 => '#F0F4FA',
                    100 => '#E1E9F5',
                    200 => '#C2D3EB',
                    300 => '#9EBBE0',
                    400 => '#7CA7D7',
                    500 => '#6891BF',
                    600 => '#587CA4',
                    700 => '#486687',
                    800 => '#39526D',
                    900 => '#2B3F55',
                    950 => '#162330',
                ],
                'danger' => [
                    50 => '#FBF1F1',
                    100 => '#F8E7E7',
                    200 => '#F1CCCB',
                    300 => '#ECB3B2',
                    400 => '#E79998',
                    500 => '#E47E7B',
                    600 => '#E05A55',
                    700 => '#C94944',
                    800 => '#892F2C',
                    900 => '#4B1614',
                    950 => '#300B0A',
                ],
                'warning' => [
                    50 => '#FFFCF8',
                    100 => '#FEF5E8',
                    200 => '#FDEFD8',
                    300 => '#FCE5BD',
                    400 => '#FCDC9E',
                    500 => '#FBD584',
                    600 => '#FBCC4F',
                    700 => '#F2C447',
                    800 => '#9C7D2A',
                    900 => '#4E3E11',
                    950 => '#2D2206',
                ],
                'success' => [
                    50 => '#EFFDF3',
                    100 => '#D5F9DF',
                    200 => '#A1F5BB',
                    300 => '#88E8A7',
                    400 => '#7FD99C',
                    500 => '#76CA91',
                    600 => '#6EBF88',
                    700 => '#65AF7D',
                    800 => '#407351',
                    900 => '#1E3B27',
                    950 => '#0F2115',
                ],
                'gray' => [
                    50 => '#F0F1F3',
                    100 => '#E3E5EA',
                    200 => '#C8CCD6',
                    300 => '#ADB3C3',
                    400 => '#929BB0',
                    500 => '#7A849A',
                    600 => '#656D80',
                    700 => '#505766',
                    800 => '#3D424E',
                    900 => '#252932',
                    950 => '#171A1F',
                ],
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Back to Application')
                    ->icon('heroicon-o-arrow-left')
                    ->url(fn () => route('dashboard'))
                    ->sort(-1),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path(path: 'Filament/Widgets'), for: 'App\Filament\Widgets')
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
            ->navigationGroups([
                'System & Administration',
                'Training',
                'Endorsements & Ratings',
                'Permissions',
                'Users & Access',
            ]);
    }
}
