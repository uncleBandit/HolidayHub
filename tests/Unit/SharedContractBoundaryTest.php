<?php

use App\Shared\Domain\Contracts\Bookable;
use Symfony\Component\Finder\Finder;

// Inspects class declarations and file contents on disk. Needs the container to
// autoload app/ classes, but must NOT touch the database.
uses(Tests\TestCase::class);

/**
 * Recursively list PHP files under a directory.
 *
 * A double-star glob looks recursive but is not: PHP's glob has no globstar
 * support, so "**" behaves as a single "*" and matches exactly one directory
 * level. The contract files sit two levels down (Shared/Domain/Contracts), so
 * the previous glob-based scan of app/Shared found nothing at all and the tests
 * below passed without inspecting a single file.
 */
function phpFilesRecursively(string $directory): array
{
    if (! is_dir($directory)) {
        return [];
    }

    return array_map(
        fn (SplFileInfo $file) => $file->getPathname(),
        iterator_to_array(
            Finder::create()->files()->name('*.php')->in($directory)->sortByName(),
            false
        )
    );
}

function sharedKernelClasses(): array
{
    $classes = [];

    foreach (phpFilesRecursively(app_path('Shared')) as $file) {
        $classes[] = 'App\\Shared\\'.str_replace(
            ['/', '.php'],
            ['\\', ''],
            trim(str_replace(app_path('Shared').'/', '', $file), '/')
        );
    }

    sort($classes);

    return $classes;
}

function bookableImplementers(): array
{
    $implementers = [];

    foreach ((array) glob(app_path('Modules').'/*/Domain/Models/*.php') as $file) {
        $class = 'App\\Modules\\'.str_replace(
            ['/', '.php'],
            ['\\', ''],
            trim(str_replace(app_path('Modules').'/', '', $file), '/')
        );

        if (class_exists($class) && in_array(Bookable::class, class_implements($class) ?: [], true)) {
            $implementers[] = $class;
        }
    }

    sort($implementers);

    return $implementers;
}

/**
 * Guards the shared contract boundary.
 *
 * app/Shared is a shared kernel, and a shared kernel is the easiest place in a
 * modular monolith to quietly rebuild the god app that module boundaries exist
 * to prevent: contracts go in, then a helper, then a DTO, then an Eloquent model
 * "just for the contract", and every module can import it again. Nothing fails
 * when that happens, so it has to be asserted.
 *
 * These tests also pin the specific regression that motivated splitting Bookable
 * in the first place.
 */
it('keeps the shared kernel to interfaces and enums', function () {
    $offenders = [];

    expect(sharedKernelClasses())->not->toBeEmpty('the recursive scan found no shared kernel files, so this guard would pass vacuously');

    foreach (sharedKernelClasses() as $class) {
        if (! class_exists($class) && ! interface_exists($class) && ! enum_exists($class)) {
            continue;
        }

        $reflection = new ReflectionClass($class);

        if ($reflection->isInterface() || $reflection->isEnum() || $reflection->isTrait()) {
            continue;
        }

        $offenders[] = $class;
    }

    expect($offenders)->toBe([], 'Concrete classes in app/Shared (must be contracts/enums only): '.implode(', ', $offenders));
})->group('architecture');

it('keeps no Eloquent models in the shared kernel', function () {
    $models = [];

    foreach (sharedKernelClasses() as $class) {
        if (! class_exists($class)) {
            continue;
        }

        if (is_subclass_of($class, Illuminate\Database\Eloquent\Model::class)) {
            $models[] = $class;
        }
    }

    expect($models)->toBe([], 'Eloquent models in app/Shared belong in a module: '.implode(', ', $models));
})->group('architecture');

it('does not let Bookable re-absorb other modules relations', function () {
    $relations = [];

    foreach ((new ReflectionClass(Bookable::class))->getMethods() as $method) {
        $type = $method->getReturnType();

        if ($type instanceof ReflectionNamedType && is_a($type->getName(), Illuminate\Database\Eloquent\Relations\Relation::class, true)) {
            $relations[] = $method->getName().'(): '.$type->getName();
        }
    }

    expect($relations)->toBe([], implode(', ', $relations).' — Bookable must describe what a bookable IS, not its relations to other modules. Declare the capability on AvailabilityAware or Pricable instead.');
})->group('architecture');

it('has no references to the retired App\Contracts namespace', function () {
    $offenders = [];

    foreach (phpFilesRecursively(app_path()) as $file) {
        if (str_contains((string) file_get_contents($file), 'App\\Contracts\\')) {
            $offenders[] = str_replace(base_path().'/', '', $file);
        }
    }

    expect($offenders)->toBe([], 'App\\Contracts was replaced by App\\Shared\\Domain\\Contracts: '.implode(', ', $offenders));
})->group('architecture');

it('has every Bookable implementer declaring availability and pricing capabilities', function () {
    $incomplete = [];

    foreach (bookableImplementers() as $class) {
        $missing = array_values(array_filter(
            [App\Shared\Domain\Contracts\AvailabilityAware::class, App\Shared\Domain\Contracts\Pricable::class],
            fn (string $contract) => ! in_array($contract, class_implements($class) ?: [], true)
        ));

        if ($missing !== []) {
            $incomplete[] = $class.' (missing '.implode(', ', array_map('class_basename', $missing)).')';
        }
    }

    expect(bookableImplementers())->not->toBeEmpty();
    expect($incomplete)->toBe([], implode('; ', $incomplete));
})->group('architecture');
