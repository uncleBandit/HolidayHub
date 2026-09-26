<?php

namespace App\Modules\Administration\Providers;

use App\Modules\Administration\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

/**
 * The SaaS platform administration panel.
 *
 * Mounted at /admin and gated on the Spatie `admin` role via
 * {@see \App\Modules\Identity\Domain\Models\User::canAccessPanel()}.
 *
 * Resources and widgets are discovered from this module's Filament directory
 * only, so the admin surface stays inside the module that owns it rather than
 * spreading Filament classes across the rest of the codebase.
 */
class AdministrationPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('administration')
            ->path('admin')
            ->login()
            ->profile()
            ->brandName(config('app.name').' Admin')
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(
                in: $this->discoverPath('Resources'),
                for: 'App\\Modules\\Administration\\Filament\\Resources',
            )
            // Widgets are not auto-discovered: PlatformOverview is listed by the
            // Dashboard page that hosts it, so it has exactly one home. A second
            // registration would render the same counters twice.
            //
            // The panel has no home page until one is registered; without it
            // /admin redirects to the first navigation item instead of showing
            // the overview.
            ->pages([
                Dashboard::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                // Filament's own generator emits PreventRequestForgery, which
                // does not exist in Laravel 12; this is the web group's CSRF
                // middleware under its current name.
                ValidateCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                ConvertEmptyStringsToNull::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * Absolute path to a subdirectory of this module's Filament directory.
     *
     * Discovery is scoped to Filament/* so the panel picks up the resources,
     * pages and widgets this module owns and nothing else.
     */
    private function discoverPath(string $directory): string
    {
        return dirname(__DIR__).'/Filament/'.$directory;
    }
}
