<?php

namespace App\Providers\Filament;

use App\Filament\Widgets\UsersShortcut;
use Awcodes\Curator\CuratorPlugin;
use Filament\Enums\ThemeMode;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Str;
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
            ->brandLogo(asset('images/iartnet-logo.jpg'))

            ->theme(asset('css/filament/admin/theme.css'))

            ->renderHook(PanelsRenderHook::HEAD_END, function (): string {
                $route = request()->route();
                $routeName = $route?->getName() ?? 'no-route';

                $safe = fn (string $s) => Str::of($s)
                    ->lower()
                    ->replace(['.', '/', ':'], '-')
                    ->replaceMatches('/[^a-z0-9\-_]/', '')
                    ->toString();

                $classes = [
                    'filament-admin',
                    'route-'.$safe($routeName),
                    'path-'.$safe(trim(request()->path(), '/')),
                ];

                $jsArray = json_encode(array_values($classes), JSON_UNESCAPED_SLASHES);

                return <<<HTML
            <script>
            document.addEventListener('DOMContentLoaded', function () {
              document.documentElement.classList.add(...{$jsArray});
            });
            </script>
            HTML;
            })

            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): string => '<link rel="stylesheet" href="'.asset('css/filament/admin/custom.css').'">'
            )

            ->defaultThemeMode(ThemeMode::Light)
            ->darkMode(false)

            ->colors([
                'primary' => Color::hex('#000000'),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                UsersShortcut::class,
            ])

            ->plugins([
                CuratorPlugin::make()
                    ->label('Media')
                    ->pluralLabel('Media')
                    ->navigationIcon('heroicon-o-photo')
                    ->navigationGroup('Content')
                    ->navigationSort(3)
                    ->navigationCountBadge()
                    ->registerNavigation(true),
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
