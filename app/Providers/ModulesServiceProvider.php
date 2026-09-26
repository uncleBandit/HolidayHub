<?php

namespace App\Providers;

use App\Support\Module;
use App\Support\ModuleRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Livewire\Component as LivewireComponent;
use Livewire\Livewire;
use Livewire\Volt\Volt;

/**
 * Bootstraps every domain module under app/Modules.
 *
 * For each module it registers, without any manual wiring:
 *   - Database/Migrations    (loaded in filename order, globally)
 *   - Database/Factories     (with a module-aware factory resolver)
 *   - Database/Seeders       (enumerated by moduleSeeders())
 *   - Presentation/Routes    (web.php and api.php, with prefixes + middleware)
 *   - Presentation/Livewire  (original alias + "<module-id>::" alias)
 *   - Resources/views        (module namespace + global view location)
 *   - Resources/lang         (namespaced under the module id)
 *   - Config/*.php           (merged as config('<module>.<file>'))
 *   - Providers/*ServiceProvider
 *
 * WHAT DELIBERATELY LIVES OUTSIDE app/Modules
 * Platform migrations that are not domain-owned — cache, jobs, telescope and the
 * cross-module integrity constraints — stay in database/migrations/, which
 * Laravel always loads alongside module paths. Shared contracts
 * (app/Contracts/Bookable.php) and the Filament panel provider
 * (app/Providers/Filament/) are application-level infrastructure, not domains,
 * so they do not pollute the module list.
 *
 * NOTE ON MIGRATION ORDER
 * Laravel sorts every discovered migration by filename, across all paths, so a
 * module's migrations are NOT isolated from other modules. Cross-module foreign
 * keys therefore depend on the numeric timestamp prefix being globally ordered.
 * When adding a migration that references another module's table, pick a
 * timestamp later than that table's own migration.
 *
 * NOTE ON ROUTE CACHING
 * Module route files must not define closures as route actions, otherwise
 * `php artisan route:cache` fails. Always reference a controller or invokable.
 *
 * NOTE ON SEEDERS
 * Each module's Database/Seeders is owned by that module and resolved
 * dynamically, so `php artisan module:seed <module-id>` seeds one bounded
 * context in isolation. A full `php artisan db:seed` still runs
 * Database\Seeders\DatabaseSeeder, because the global seeding ORDER is a
 * cross-module constraint and therefore cannot be left to alphabetical
 * discovery order.
 */
class ModulesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Must be app_path(), not basePath(): basePath() is the project root,
        // so basePath('Modules') would resolve to <root>/Modules and discover
        // zero modules.
        $this->app->singleton(ModuleRegistry::class, static fn ($app): ModuleRegistry => new ModuleRegistry(
            $app->path('Modules'),
        ));
    }

    public function boot(ModuleRegistry $registry): void
    {
        $this->registerFactoryResolver();
        $this->registerMorphMap($registry);

        foreach ($registry->enabled() as $module) {
            $this->registerModule($module);
        }

        // Must run after every module has contributed its view directory, and
        // after the View facade is resolvable.
        $this->registerVoltComponents($registry);
    }

    private function registerModule(Module $module): void
    {
        $this->registerConfig($module);
        $this->registerTranslations($module);
        $this->registerViews($module);
        $this->registerMigrations($module);
        $this->registerFactories($module);
        $this->registerRoutes($module);
        $this->registerLivewireComponents($module);

        foreach ($module->providers() as $provider) {
            $this->app->register($provider);
        }
    }

    /**
     * Config/<file>.php becomes config('<module id>.<file>').
     */
    private function registerConfig(Module $module): void
    {
        $directory = $module->path('Config');

        if (! is_dir($directory)) {
            return;
        }

        foreach ((array) glob($directory.'/*.php') as $file) {
            $key = $module->id.'.'.pathinfo((string) $file, PATHINFO_FILENAME);

            $this->mergeConfigFrom($file, $key);
        }
    }

    private function registerTranslations(Module $module): void
    {
        $path = $module->path('Resources/lang');

        if (is_dir($path)) {
            $this->loadTranslationsFrom($path, $module->id);
        }
    }

    /**
     * Module views are registered twice, on purpose.
     *
     * loadViewsFrom() gives the module a namespace, e.g. `hotel::livewire.hotel.show`,
     * which is how a module should reach its own views.
     *
     * View::addLocation() is what keeps the pre-modularisation view names alive.
     * The ~59 livewire Blade files are referenced as `livewire.hotel.hotel_show`,
     * and each component's own render() returns that same global name. Adding
     * the module's Resources/views as a plain view location means those names
     * keep resolving after the files move into app/Modules, so no Blade file and
     * no render() call had to be rewritten.
     *
     * Every global view name is owned by exactly one module, so the extra
     * locations cannot shadow each other.
     */
    private function registerViews(Module $module): void
    {
        $path = $module->path('Resources/views');

        if (! is_dir($path)) {
            return;
        }

        $this->loadViewsFrom($path, $module->id);

        View::addLocation($path);
    }

    private function registerMigrations(Module $module): void
    {
        $path = $module->path('Database/Migrations');

        if (is_dir($path)) {
            $this->loadMigrationsFrom($path);
        }
    }

    private function registerFactories(Module $module): void
    {
        $path = $module->path('Database/Factories');

        if (is_dir($path)) {
            $this->loadFactoriesFrom($path);
        }
    }

    /**
     * Web routes keep their own URI space; API routes are prefixed and versioned.
     */
    private function registerRoutes(Module $module): void
    {
        $web = $module->file('Presentation/Routes/web.php');

        if ($web !== null) {
            Route::middleware($module->webMiddleware())
                ->name($module->routeNamePrefix())
                ->group($web);
        }

        $api = $module->file('Presentation/Routes/api.php');

        if ($api !== null) {
            Route::prefix($module->apiPrefix())
                ->middleware($module->apiMiddleware())
                ->name($module->routeNamePrefix().'api.')
                ->group($api);
        }
    }

    /**
     * Register every Livewire component in Presentation/Livewire.
     *
     * Two aliases are registered per component:
     *
     *  1. The original pre-modularisation alias, derived from the component's
     *     path relative to Presentation/Livewire, e.g.
     *     Presentation/Livewire/Hotel/HotelShow.php -> "hotel.hotel-show".
     *     Keeping this alias is what lets every existing
     *     `<livewire:hotel.hotel-show />` in resources/views keep working
     *     untouched.
     *
     *  2. The namespaced alias "<module-id>::<kebab-class>", which is the
     *     canonical way to address the component from inside a module.
     *
     * Livewire's built-in auto-discovery only scans the `App\Livewire`
     * namespace, so without these explicit registrations moving the classes
     * into App\Modules would silently unregister all 50 components.
     */
    private function registerLivewireComponents(Module $module): void
    {
        if (! class_exists(Livewire::class)) {
            return;
        }

        $directory = $module->path('Presentation/Livewire');

        if (! is_dir($directory)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
        );

        /** @var \SplFileInfo $file */
        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $relative = Str::beforeLast(
                Str::after($file->getPathname(), $directory.'/'),
                '.php',
            );

            $class = $module->namespace().'\\Presentation\\Livewire\\'
                .str_replace('/', '\\', $relative);

            if (! class_exists($class)) {
                continue;
            }

            // Forms and invokables also live in this directory; only real
            // components are addressable as Livewire components.
            if (! is_subclass_of($class, LivewireComponent::class)) {
                continue;
            }

            $alias = collect(explode('/', $relative))
                ->map(static fn (string $segment): string => Str::kebab($segment))
                ->implode('.');

            Livewire::component($alias, $class);
            Livewire::component($module->id.'::'.Str::kebab(class_basename($class)), $class);
        }
    }

    /**
     * Register Volt's view-only components (e.g. `settings.profile`).
     *
     * Volt discovers these by scanning a *mounted directory*, and its default
     * mount is `resources/views/livewire` + `resources/views/pages`. Those
     * directories are now empty because the views moved into their modules, so
     * without an explicit mount Volt would stop registering auth.login,
     * auth.register, auth.verify-email and the four settings.* components.
     *
     * Mounting each module's Resources/views/livewire keeps the component names
     * identical, because a component is named by its path relative to the
     * mount point: mounting app/Modules/Auth/Resources/views/livewire still
     * yields "auth.login" for auth/login.blade.php.
     */
    private function registerVoltComponents(ModuleRegistry $registry): void
    {
        if (! class_exists(Volt::class)) {
            return;
        }

        $paths = [];

        foreach ($registry->enabled() as $module) {
            $path = $module->path('Resources/views/livewire');

            if (is_dir($path)) {
                $paths[] = $path;
            }
        }

        if ($paths !== []) {
            Volt::mount($paths);
        }
    }

    /**
     * Teach Laravel where a module model's factory lives.
     *
     * The default resolver assumes Database\Factories\XFactory, which does not
     * apply to App\Modules\Booking\Domain\Models\Booking.
     */
    private function registerFactoryResolver(): void
    {
        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            if (! Str::startsWith($modelName, 'App\\Modules\\')) {
                return 'Database\\Factories\\'.class_basename($modelName).'Factory';
            }

            $module = Str::before(Str::after($modelName, 'App\\Modules\\'), '\\');
            $factory = class_basename($modelName).'Factory';

            return "App\\Modules\\{$module}\\Database\\Factories\\{$factory}";
        });

        // NOTE: there is deliberately no global model-name resolver here.
        //
        // Factory::guessModelNamesUsing() writes to
        // static::$modelNameResolvers[static::class], keyed on the CALLING
        // class — so calling it from this provider registers a resolver for
        // ModulesServiceProvider, which Factory::modelName() never consults.
        // It cannot be installed globally.
        //
        // Instead every module factory declares an explicit `protected $model`.
        // That is required, not cosmetic: a factory that omits $model falls
        // through to Laravel's default resolver, which strips the (now wrong)
        // static $namespace of 'Database\Factories\' from
        // App\Modules\Identity\Database\Factories\UserFactory, fails to find
        // the result, and silently returns 'App\User' — a class that does not
        // exist. See tests/Unit/ModuleFactoryModelDeclarationTest.php, which
        // fails the build if any module factory omits $model.
    }

    /**
     * Register the polymorphic morph map from module manifests.
     *
     * A module may declare:
     *   "morph_map": { "hotel": "App\\Modules\\Accommodation\\Domain\\Models\\Hotel" }
     *
     * Keeping this declarative means the map can no longer drift out of sync
     * with the model namespaces, which is how polymorphic data gets corrupted.
     */
    private function registerMorphMap(ModuleRegistry $registry): void
    {
        $map = [];

        foreach ($registry->enabled() as $module) {
            foreach ((array) ($module->manifest['morph_map'] ?? []) as $alias => $class) {
                // Two modules claiming one alias is a data-corruption bug: the
                // morph column would resolve to whichever model happened to be
                // registered last, and existing rows would stop resolving.
                if (isset($map[$alias]) && $map[$alias] !== $class) {
                    throw new \RuntimeException(sprintf(
                        'Morph alias "%s" is claimed by both %s and %s.',
                        $alias,
                        $map[$alias],
                        $class,
                    ));
                }

                $map[$alias] = $class;
            }
        }

        if ($map === []) {
            return;
        }

        // enforceMorphMap, not morphMap: an unmapped model must fail loudly here
        // rather than writing a fully-qualified class name into a morph column
        // that a later refactor would orphan.
        \Illuminate\Database\Eloquent\Relations\Relation::enforceMorphMap($map);
    }

    /**
     * Seeders declared by modules, keyed by module id.
     *
     * Thin wrapper over {@see ModuleRegistry::seedersByModule()}, kept because
     * `php artisan module:seed` and external tooling both resolve module
     * ownership through this provider.
     *
     * @return array<string, array<int, class-string>>
     */
    public function moduleSeeders(ModuleRegistry $registry): array
    {
        return $registry->seedersByModule();
    }
}
