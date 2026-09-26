<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Discovers domain modules under app/Modules.
 *
 * Discovery is filesystem based rather than hard-coded, so a new module only
 * needs a directory (and optionally a module.json) — never a bootstrap edit.
 * The result is cached for the lifetime of the process.
 */
final class ModuleRegistry
{
    /** @var array<string, Module>|null */
    private ?array $modules = null;

    public function __construct(
        private readonly string $basePath,
    ) {}

    /**
     * @return array<string, Module>
     */
    public function all(): array
    {
        return $this->modules ??= $this->discover();
    }

    public function get(string $id): ?Module
    {
        return $this->all()[$id] ?? null;
    }

    public function has(string $id): bool
    {
        return isset($this->all()[$id]);
    }

    /**
     * @return array<int, Module>
     */
    public function enabled(): array
    {
        return array_values(array_filter(
            $this->all(),
            static fn (Module $module): bool => $module->isEnabled(),
        ));
    }

    /**
     * Seeders grouped by owning module id, skipping modules that own none.
     *
     * Note this reports ownership only. Order within a module comes from
     * Module::seeders(); the order *between* modules stays explicit in
     * Database\Seeders\DatabaseSeeder, because it is a real cross-module
     * constraint that cannot be inferred from alphabetical module order.
     *
     * @return array<string, array<int, class-string>>
     */
    public function seedersByModule(): array
    {
        $seeders = [];

        foreach ($this->enabled() as $module) {
            $owned = $module->seeders();

            if ($owned !== []) {
                $seeders[$module->id] = $owned;
            }
        }

        return $seeders;
    }

    /**
     * Forget the cache. Useful in tests that create modules on the fly.
     */
    public function flush(): void
    {
        $this->modules = null;
    }

    /**
     * @return array<string, Module>
     */
    private function discover(): array
    {
        if (! is_dir($this->basePath)) {
            return [];
        }

        $modules = [];

        foreach ((array) scandir($this->basePath) as $entry) {
            if ($entry === '.' || $entry === '..' || $entry[0] === '_') {
                continue;
            }

            $path = $this->basePath.'/'.$entry;

            if (! is_dir($path)) {
                continue;
            }

            $module = $this->makeModule($entry, $path);

            if ($module !== null) {
                $modules[$module->id] = $module;
            }
        }

        ksort($modules);

        return $modules;
    }

    private function makeModule(string $directory, string $path): ?Module
    {
        $manifest = $this->readManifest($path);

        $id = $manifest['id'] ?? Str::kebab($directory);
        $name = $manifest['name'] ?? Str::headline($directory);

        return new Module(
            id: (string) $id,
            name: (string) $name,
            path: $path,
            manifest: $manifest,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function readManifest(string $path): array
    {
        $file = $path.'/module.json';

        if (! is_file($file)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($file), true);

        return is_array($decoded) ? $decoded : [];
    }
}
