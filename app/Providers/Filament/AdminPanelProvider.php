<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Http\Middleware\HandleAppearance;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function boot(): void
    {
        // Applies to nearly every action panel-wide (buttons, row actions, modal submit
        // buttons, etc.) via ->defaultColor(), which only takes effect where a color hasn't
        // already been set explicitly — so DeleteAction/DeleteBulkAction's own ->defaultColor('danger')
        // (set later, since it's declared on the more specific subclass) still wins, keeping
        // destructive actions red while everything else gets this neutral blue instead of the
        // brand-red 'primary' color.
        Action::configureUsing(fn (Action $action) => $action->defaultColor('info'));

        // EditAction is the one built-in action that also sets its own ->defaultColor()
        // (to 'primary') inside its setUp(), which — because subclass setUp() always runs
        // after the Action::configureUsing() callback above in Filament's configuration
        // order — overrides it back to the brand-red 'primary' color. Override it a second
        // time, specifically for EditAction, so every edit button in the panel is blue too.
        EditAction::configureUsing(fn (EditAction $action) => $action->defaultColor('info'));
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->defaultThemeMode(ThemeMode::System)
            ->themeSwitcher(false)
            ->brandName('vatger Training System')
            ->favicon(asset('favicon.ico'))
            ->brandLogo(fn () => Vite::asset('resources/js/images/vatger-training-light.svg'))
            ->darkModeBrandLogo(fn () => Vite::asset('resources/js/images/vatger-training-dark.svg'))
            ->brandLogoHeight('2.25rem')
            ->colors([
                // VATGER brand palette — sourced from @vatger/basecss/vars.css so the
                // admin panel uses the exact same scales as the rest of the application.
                'gray' => [
                    50 => 'oklch(0.958 0.003 264.69)',
                    100 => 'oklch(0.922 0.007 268.62)',
                    200 => 'oklch(0.845 0.015 268.51)',
                    300 => 'oklch(0.767 0.024 269.39)',
                    400 => 'oklch(0.689 0.033 267.09)',
                    500 => 'oklch(0.613 0.035 265.99)',
                    600 => 'oklch(0.535 0.031 267.28)',
                    700 => 'oklch(0.456 0.026 265.53)',
                    800 => 'oklch(0.379 0.022 267.47)',
                    900 => 'oklch(0.281 0.017 266.38)',
                    950 => 'oklch(0.217 0.011 260.68)',
                ],
                'primary' => [
                    50 => 'oklch(0.967 0.012 17.51)',
                    100 => 'oklch(0.925 0.029 15.09)',
                    200 => 'oklch(0.861 0.056 15.58)',
                    300 => 'oklch(0.787 0.094 16.13)',
                    400 => 'oklch(0.724 0.133 17.42)',
                    500 => 'oklch(0.654 0.181 18.94)',
                    600 => 'oklch(0.557 0.183 20.14)',
                    700 => 'oklch(0.45 0.148 19.96)',
                    800 => 'oklch(0.351 0.115 20.29)',
                    900 => 'oklch(0.244 0.08 20.4)',
                    950 => 'oklch(0.196 0.065 20.49)',
                ],
                'danger' => [
                    50 => 'oklch(0.966 0.011 17.51)',
                    100 => 'oklch(0.942 0.019 17.58)',
                    200 => 'oklch(0.876 0.042 19.85)',
                    300 => 'oklch(0.819 0.067 19.79)',
                    400 => 'oklch(0.76 0.094 20.31)',
                    500 => 'oklch(0.705 0.127 22.57)',
                    600 => 'oklch(0.64 0.169 25.16)',
                    700 => 'oklch(0.58 0.164 25.81)',
                    800 => 'oklch(0.437 0.124 25.52)',
                    900 => 'oklch(0.286 0.081 25.74)',
                    950 => 'oklch(0.214 0.061 25.19)',
                ],
                'warning' => [
                    50 => 'oklch(0.992 0.006 75.39)',
                    100 => 'oklch(0.974 0.02 77.31)',
                    200 => 'oklch(0.957 0.034 80.3)',
                    300 => 'oklch(0.931 0.058 81.44)',
                    400 => 'oklch(0.907 0.086 83.52)',
                    500 => 'oklch(0.888 0.108 84.89)',
                    600 => 'oklch(0.864 0.148 87.9)',
                    700 => 'oklch(0.839 0.147 88.09)',
                    800 => 'oklch(0.604 0.106 87.76)',
                    900 => 'oklch(0.372 0.065 88.82)',
                    950 => 'oklch(0.258 0.045 87.16)',
                ],
                'success' => [
                    50 => 'oklch(0.981 0.02 154.81)',
                    100 => 'oklch(0.949 0.051 153.92)',
                    200 => 'oklch(0.901 0.115 153.44)',
                    300 => 'oklch(0.855 0.129 153.24)',
                    400 => 'oklch(0.813 0.123 153.22)',
                    500 => 'oklch(0.77 0.116 153.2)',
                    600 => 'oklch(0.738 0.113 153.11)',
                    700 => 'oklch(0.692 0.105 153.28)',
                    800 => 'oklch(0.51 0.078 153.57)',
                    900 => 'oklch(0.323 0.05 152.7)',
                    950 => 'oklch(0.228 0.034 153.71)',
                ],
                // A normal, recognisable blue for neutral/utility actions (Add, Edit,
                // Remove-from-list, etc.) — distinct from both the brand-red 'primary' and
                // Filament's stock "info" blue (which is noticeably more saturated still).
                'info' => [
                    50 => 'oklch(0.97 0.016 255)',
                    100 => 'oklch(0.932 0.035 256)',
                    200 => 'oklch(0.882 0.065 254)',
                    300 => 'oklch(0.809 0.098 253)',
                    400 => 'oklch(0.723 0.135 254)',
                    500 => 'oklch(0.623 0.17 255)',
                    600 => 'oklch(0.546 0.175 257)',
                    700 => 'oklch(0.478 0.16 258)',
                    800 => 'oklch(0.398 0.13 259)',
                    900 => 'oklch(0.32 0.095 261)',
                    950 => 'oklch(0.24 0.065 263)',
                ],
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Back to Application')
                    ->icon('heroicon-o-arrow-left')
                    ->url(fn () => route('dashboard'))
                    ->sort(-1),
                'logout' => fn (Action $action) => $action->hidden(),
            ])
            ->renderHook(
                PanelsRenderHook::HEAD_START,
                fn () => Blade::render(
                    '<script>localStorage.setItem(\'theme\', @js($theme));</script>',
                    ['theme' => view()->shared('theme', 'system')],
                ),
            )
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn () => new HtmlString(<<<'HTML'
                    <style>
                        /* Relation entry tables (RepeatableEntry) switch between a compact <table>
                           layout and a much larger stacked "card" layout below a 36rem container
                           width (Filament's own @container query). Force the compact table layout
                           unconditionally, at every screen size, since the stacked layout takes up
                           far too much vertical space with more than a couple of rows. */
                        .fi-in-table-repeatable > table {
                            display: table !important;
                        }
                        .fi-in-table-repeatable > table > thead {
                            display: table-header-group !important;
                            white-space: normal !important;
                        }
                        .fi-in-table-repeatable > table > tbody {
                            display: table-row-group !important;
                        }
                        .fi-in-table-repeatable > table > tbody > tr {
                            display: table-row !important;
                            padding: 0 !important;
                            gap: 0 !important;
                        }
                        .fi-in-table-repeatable > table > tbody > tr > td {
                            display: table-cell !important;
                        }
                        .fi-in-table-repeatable > table > tbody > tr > td .fi-in-entry-label {
                            display: none !important;
                        }
                        .fi-in-table-repeatable > table > tbody > tr > td .fi-in-entry {
                            row-gap: 0 !important;
                        }

                        /* Tighter padding on top of the forced table layout, since the default
                           table padding is still fairly generous with many rows. */
                        .fi-in-table-repeatable > table > thead > tr > th {
                            padding-inline: 0.5rem !important;
                            padding-block: 0.25rem !important;
                        }
                        .fi-in-table-repeatable > table > tbody > tr > td {
                            padding-inline: 0.5rem !important;
                            padding-block: 0.25rem !important;
                        }
                    </style>
                    HTML),
            )
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\Filament\Clusters')
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
                HandleAppearance::class,
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
