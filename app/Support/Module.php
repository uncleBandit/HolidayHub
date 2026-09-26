<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Value object describing a single domain module.
 *
 * A module is a directory under app/Modules with an optional module.json
 * manifest. Every conventional subdirectory is auto-discovered and registered
 * by {@see \App\Providers\ModulesServiceProvider}, so adding a module requires
 * no edits to bootstrap/providers.php.
 */
final readonly class Module
{
    /**
     * @param  array<string, mixed>  $manifest
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $path,
        public array $manifest = [],
    ) {}

    /**
     * Resolve a path inside the module, e.g. path('Database/Migrations').
     */
    public function path(string $relative = ''): string
    {
        $base = rtrim($this->path, '/');

        return $relative === '' ? $base : $base.'/'.ltrim($relative, '/');
    }

    public function exists(string $relative): bool
    {
        return is_dir($this->path($relative));
    }

    public function file(string $relative): ?string
    {
        $path = $this->path($relative);

        return is_file($path) ? $path : null;
    }

    /**
     * Absolute PHP namespace for this module, e.g. App\Modules\Booking.
     */
    public function namespace(): string
    {
        return 'App\\Modules\\'.$this->classSegment();
    }

    /**
     * StudlyCase segment used in namespaces, e.g. Booking.
     */
    public function classSegment(): string
    {
        return Str::studly($this->id);
    }

    /**
     * Prefix for API routes. Defaults to api/v1.
     */
    public function apiPrefix(): string
    {
        return (string) ($this->manifest['api_prefix'] ?? 'api/v1');
    }

    /**
     * @return array<int, string>
     */
    public function apiMiddleware(): array
    {
        return (array) ($this->manifest['api_middleware'] ?? ['api']);
    }

    /**
     * @return array<int, string>
     */
    public function webMiddleware(): array
    {
        return (array) ($this->manifest['web_middleware'] ?? ['web']);
    }

    public function description(): string
    {
        return (string) ($this->manifest['description'] ?? '');
    }

    /**
     * Whether this module may be skipped entirely.
     */
    public function isEnabled(): bool
    {
        return (bool) ($this->manifest['enabled'] ?? true);
    }

    /**
     * Route name prefix, e.g. "booking.".
     */
    public function routeNamePrefix(): string
    {
        return (string) ($this->manifest['route_name_prefix'] ?? $this->id.'.');
    }

    /**
     * Database seeders this module owns, from Database/Seeders/*Seeder.php.
     *
     * Ownership is derived from the directory, not from a manifest, so adding a
     * seeder to a module needs no registration anywhere.
     *
     * ORDER is not alphabetical by default, because seeding has real
     * intra-module dependencies: Identity's AdminSeeder calls assignRole('admin')
     * and cannot run before RoleSeeder has created that role. A module that has
     * such dependencies declares them explicitly in module.json:
     *
     *   "seeders": ["RoleSeeder", "GuestSeeder", "AdminSeeder"]
     *
     * Every listed class must exist; an unknown name is a hard error rather than
     * a silent skip, so a typo cannot quietly drop a seeder.
     *
     * Note there is deliberately no module-level "depends on other module" key.
     * Cross-module seeder prerequisites are already enforced inside the seeders
     * themselves — HotelSeeder and OfferSeeder both early-return when their
     * prerequisites are absent, and DestinationSeeder calls AmenitySeeder itself
     * when no amenities exist. A declarative module graph was tried and removed:
     * it was not acyclic (destinations seeds amenities from catalog, while
     * catalog's OfferSeeder needs destinations), so it could only ever be a
     * guess about ordering that the seeders already handle more accurately.
     *
     * @return array<int, class-string>
     */
    public function seeders(): array
    {
        $directory = $this->path('Database/Seeders');

        if (! is_dir($directory)) {
            return [];
        }

        $discovered = [];

        foreach ((array) glob($directory.'/*Seeder.php') as $file) {
            $discovered[basename((string) $file, '.php')] = true;
        }

        $ordered = (array) ($this->manifest['seeders'] ?? []);

        $names = $ordered === []
            ? array_keys($discovered)
            : array_map(static fn (string|int $name): string => (string) $name, $ordered);

        $seeders = [];

        foreach ($names as $name) {
            if (! isset($discovered[$name])) {
                throw new \RuntimeException(sprintf(
                    'Module [%s] manifest declares seeder [%s], but %s does not exist.',
                    $this->id,
                    $name,
                    $this->path('Database/Seeders/'.$name.'.php'),
                ));
            }

            $class = $this->namespace().'\\Database\\Seeders\\'.$name;

            if (class_exists($class)) {
                $seeders[] = $class;
            }
        }

        return $seeders;
    }

    /**
     * Service providers declared by the module itself, relative to Providers/.
     *
     * By default every Providers/*ServiceProvider.php is discovered. A module
     * that needs a provider with a different suffix — Filament's PanelProvider,
     * for instance — may list it explicitly in module.json:
     *
     *   "providers": ["AdministrationPanelProvider"]
     *
     * Entries may be a short name relative to this module's Providers/
     * directory, or a fully-qualified class name.
     *
     * @return array<int, class-string>
     */
    public function providers(): array
    {
        $declared = $this->manifest['providers'] ?? null;

        if (is_array($declared)) {
            $providers = [];

            foreach ($declared as $name) {
                $class = str_contains((string) $name, '\\')
                    ? (string) $name
                    : $this->namespace().'\\Providers\\'.$name;

                if (class_exists($class)) {
                    $providers[] = $class;
                }
            }

            return $providers;
        }

        $directory = $this->path('Providers');

        if (! is_dir($directory)) {
            return [];
        }

        $providers = [];

        foreach ((array) glob($directory.'/*ServiceProvider.php') as $file) {
            $class = $this->namespace().'\\Providers\\'.basename((string) $file, '.php');

            if (class_exists($class)) {
                $providers[] = $class;
            }
        }

        sort($providers);

        return $providers;
    }
}
