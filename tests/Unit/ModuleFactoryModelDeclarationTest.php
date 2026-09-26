<?php

use App\Support\ModuleRegistry;
use Illuminate\Database\Eloquent\Factories\Factory;

// This test needs the service container (to resolve ModuleRegistry), but it must
// NOT refresh the database: it only inspects class declarations on disk.
uses(Tests\TestCase::class);

/**
 * Guards the module factory wiring.
 *
 * A module factory that omits `protected $model` does not fail loudly. It
 * falls through to Laravel's default resolver, which strips the stale
 * static $namespace of 'Database\Factories\' from a namespaced module factory,
 * fails to resolve the result, and silently returns 'App\X' — a class that
 * does not exist. That breakage only surfaces when a seeder or test happens to
 * call that factory, which is exactly how it reached production unnoticed.
 *
 * This test drives the real ModuleRegistry rather than globbing app/Modules, so
 * it also fails if module discovery itself regresses.
 */
function moduleFactoryClasses(ModuleRegistry $registry): array
{
    $classes = [];

    foreach ($registry->enabled() as $module) {
        foreach ((array) glob($module->path('Database/Factories').'/*Factory.php') as $file) {
            $classes[] = $module->namespace().'\\Database\\Factories\\'.basename((string) $file, '.php');
        }
    }

    sort($classes);

    return $classes;
}

function moduleModelClasses(ModuleRegistry $registry): array
{
    $classes = [];

    foreach ($registry->enabled() as $module) {
        foreach ((array) glob($module->path('Domain/Models').'/*.php') as $file) {
            $classes[$module->id][] = $module->namespace().'\\Domain\\Models\\'.basename((string) $file, '.php');
        }
    }

    ksort($classes);

    return $classes;
}

it('discovers every module', function () {
    expect(moduleFactoryClasses(app(ModuleRegistry::class)))->not->toBeEmpty();
})->group('modules');

it('every module factory declares an explicit $model', function () {
    $missing = [];

    foreach (moduleFactoryClasses(app(ModuleRegistry::class)) as $class) {
        $declared = (new ReflectionClass($class))->getDefaultProperties()['model'] ?? null;

        if ($declared === null || $declared === '') {
            $missing[] = $class;
        }
    }

    expect($missing)->toBe([], 'Factories without an explicit $model: '.implode(', ', $missing));
})->group('modules');

it('every module factory model actually exists', function () {
    $broken = [];

    foreach (moduleFactoryClasses(app(ModuleRegistry::class)) as $class) {
        /** @var Factory $factory */
        $factory = $class::new();

        if (! class_exists($factory->modelName())) {
            $broken[] = $class.' -> '.$factory->modelName();
        }
    }

    expect($broken)->toBe([], 'Factories pointing at missing models: '.implode(', ', $broken));
})->group('modules');

it('every module model has a factory in its own module', function () {
    $missing = [];

    foreach (moduleModelClasses(app(ModuleRegistry::class)) as $module => $classes) {
        foreach ($classes as $class) {
            $factory = "App\\Modules\\{$module}\\Database\\Factories\\".class_basename($class).'Factory';

            if (! class_exists($factory)) {
                $missing[] = $class;
            }
        }
    }

    expect($missing)->toBe([], 'Models with no factory in their module: '.implode(', ', $missing));
})->group('modules');
