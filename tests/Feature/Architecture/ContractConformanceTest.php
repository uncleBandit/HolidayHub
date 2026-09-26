<?php

use App\Shared\Domain\Contracts\AvailabilityAware;
use App\Shared\Domain\Contracts\Bookable;
use App\Shared\Domain\Contracts\HasUnitCapacity;
use App\Shared\Domain\Contracts\Pricable;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;

/**
 * Proves the shared contracts are satisfied against the real schema, not just
 * against the class declaration.
 *
 * These are all regressions that were live until the PricingEngine was rewritten
 * and nothing caught them, because PHP happily accepts a class that declares
 * `seasonalRates(): Relation` and then returns a hasMany() against a column that
 * does not exist. The failure only appears at query time, as a QueryException or
 * a ClassMorphViolationException, in a code path no test covered.
 *
 * Two specific shapes of defect are pinned here:
 *
 *   1. A relation that is not a morph. Experience::seasonalRates() was
 *      hasMany(SeasonalRate::class), which resolves to a foreign key of
 *      `experience_id`. seasonal_rates has no such column; it has
 *      seasonal_rateable_type and seasonal_rateable_id. Every seasonal rate
 *      lookup for an Experience therefore threw.
 *
 *   2. A morph target missing from the enforced morph map. Relation::enforceMorphMap()
 *      is on, so touching a morph relation on an unregistered model throws
 *      ClassMorphViolationException. Experience, Offer, Availability and Image
 *      were all unregistered even though they are morph targets.
 */
function bookableClasses(): array
{
    $found = [];

    foreach (array_merge(
        (array) glob(app_path('Modules').'/*/Domain/Models/*.php'),
        (array) glob(app_path('Models').'/*.php')
    ) as $file) {
        // app_path() has no trailing slash, so the relative path starts with one.
        $relative = ltrim(str_replace(app_path(), '', (string) $file), '/');
        $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], $relative);

        if (class_exists($class) && in_array(Bookable::class, class_implements($class) ?: [], true)) {
            $found[] = $class;
        }
    }

    sort($found);

    return $found;
}

/**
 * The three relations the capabilities promise, with the morph name each must use.
 */
function capabilityRelations(): array
{
    return [
        'availabilities' => [AvailabilityAware::class, 'bookable', 'availabilities'],
        'seasonalRates' => [Pricable::class, 'seasonal_rateable', 'seasonal_rates'],
        'offers' => [Pricable::class, 'offerable', 'offers'],
    ];
}

it('finds the bookables to check', function () {
    expect(bookableClasses())->toContain(
        App\Modules\Accommodation\Domain\Models\Hotel::class,
        App\Modules\Activities\Domain\Models\Experience::class,
        App\Modules\Packages\Domain\Models\Package::class,
    );
})->group('architecture');

it('implements every capability relation as a morph against columns that exist', function () {
    $offenders = [];

    foreach (bookableClasses() as $class) {
        foreach (capabilityRelations() as $method => [$contract, $morphName, $table]) {
            if (! in_array($contract, class_implements($class) ?: [], true)) {
                continue;
            }

            $relation = (new $class)->{$method}();

            if (! $relation instanceof MorphMany) {
                $offenders[] = sprintf(
                    '%s::%s() returns %s, not a MorphMany. A non-morph relation resolves to a foreign key that does not exist on %s.',
                    class_basename($class),
                    $method,
                    $relation::class,
                    $table
                );

                continue;
            }

            // On a MorphMany, getMorphClass() is the *parent's* alias ("hotel")
            // while getMorphType() is the foreign column on the related table
            // ("bookable_type"). The relationship name we care about is the
            // latter, so strip the suffix rather than comparing getMorphClass().
            if ($relation->getMorphType() !== $morphName.'_type') {
                $offenders[] = sprintf(
                    '%s::%s() morphs on "%s", expected the "%s" columns.',
                    class_basename($class),
                    $method,
                    $relation->getMorphType(),
                    $morphName
                );
            }

            // The columns the relation will actually query.
            $required = [
                $relation->getForeignKeyName(),
                $relation->getMorphType(),
            ];

            foreach (array_unique($required) as $column) {
                if (! Schema::hasColumn($relation->getRelated()->getTable(), $column)) {
                    $offenders[] = sprintf(
                        '%s::%s() queries %s.%s, which does not exist.',
                        class_basename($class),
                        $method,
                        $relation->getRelated()->getTable(),
                        $column
                    );
                }
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
})->group('architecture');

it('does not throw from a capability relation', function () {
    $offenders = [];

    foreach (bookableClasses() as $class) {
        foreach (array_keys(capabilityRelations()) as $method) {
            if (! method_exists($class, $method)) {
                continue;
            }

            try {
                (new $class)->{$method}();
            } catch (Throwable $e) {
                // Package::availabilities() used to throw BadMethodCallException
                // with a comment explaining that packages "don't have per-day
                // availability" — while declaring the interface that requires it.
                $offenders[] = sprintf('%s::%s() throws %s: %s', class_basename($class), $method, $e::class, $e->getMessage());
            }
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
})->group('architecture');

it('registers every model reached through a morph relation in the enforced morph map', function () {
    $map = Relation::morphMap();
    $offenders = [];

    foreach (bookableClasses() as $class) {
        if (! in_array($class, $map, true)) {
            $offenders[] = class_basename($class).' is a bookable but is not in the morph map, so every morph relation on it throws ClassMorphViolationException';
        }
    }

    // Morph targets reached from the relations above, checked directly.
    foreach ([
        App\Modules\Catalog\Domain\Models\Offer::class,
        App\Modules\Availability\Domain\Models\Availability::class,
        App\Modules\Pricing\Domain\Models\SeasonalRate::class,
        App\Modules\Booking\Domain\Models\Booking::class,
    ] as $target) {
        if (! in_array($target, $map, true)) {
            $offenders[] = class_basename($target).' is a morph target but is not in the morph map';
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
})->group('architecture');

it('reports a unit capacity of at least one for every bookable that opts in', function () {
    $offenders = [];

    foreach (bookableClasses() as $class) {
        if (! in_array(HasUnitCapacity::class, class_implements($class) ?: [], true)) {
            continue;
        }

        $model = new $class;

        if (! method_exists($model, 'unitCapacity')) {
            $offenders[] = class_basename($class).' implements HasUnitCapacity but has no unitCapacity()';

            continue;
        }

        // Unpersisted models are enough to prove the method exists and does not
        // divide by zero; the real assertion is that the declared capacity is
        // never below one, which the guest-count maths depends on.
        $capacity = $model->unitCapacity();

        if ($capacity < 1) {
            $offenders[] = class_basename($class).'::unitCapacity() returned '.$capacity.', which would divide by zero in demand pricing';
        }
    }

    expect($offenders)->toBe([], implode("\n", $offenders));
})->group('architecture');
