<?php

namespace App\Modules\Administration\Providers;

use App\Modules\Administration\Filament\Pages\Dashboard;
use App\Modules\Administration\Filament\Resources\BookingOperationsResource;
use App\Modules\Audit\Filament\Resources\AuditLogResource;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
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
            // The site's palette and type, so the panel reads as part of
            // HolidayHub: forest for actions, clay for warnings.
            ->brandLogo(fn () => view('administration::admin.brand'))
            ->brandLogoHeight('1.9rem')
            ->favicon(asset('favicon.svg'))
            ->font('DM Sans')
            ->colors([
                'primary' => Color::hex('#16392F'),
                'warning' => Color::hex('#D85F3C'),
                'danger' => Color::Rose,
                'success' => Color::Emerald,
                'info' => Color::Sky,
                'gray' => Color::Stone,
            ])
            ->renderHook(PanelsRenderHook::HEAD_END, fn () => view('administration::admin.head'))
            ->sidebarCollapsibleOnDesktop()
            ->navigationGroups([
                NavigationGroup::make('Marketplace'),
                NavigationGroup::make('Bookings & money'),
                NavigationGroup::make('People'),
                NavigationGroup::make('Content & catalog'),
                NavigationGroup::make('System')->collapsed(),
            ])
            ->userMenuItems([
                Action::make('site')
                    ->label('View the site')
                    ->icon('heroicon-o-globe-alt')
                    ->url(fn () => route('welcome')),
            ])
            ->discoverResources(
                in: $this->discoverPath('Resources'),
                for: 'App\\Modules\\Administration\\Filament\\Resources',
            )
            ->resources([
                AuditLogResource::class,
                BookingOperationsResource::class,
            ])
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
            // Other pages (e.g. Revenue & payouts) are discovered like resources.
            ->discoverPages(
                in: $this->discoverPath('Pages'),
                for: 'App\\Modules\\Administration\\Filament\\Pages',
            )
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
