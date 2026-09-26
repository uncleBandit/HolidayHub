<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * PostgreSQL has no UNSIGNED integer type, so Laravel silently drops the
 * `unsignedInteger` / `unsignedTinyInteger` / `unsignedBigInteger` modifier when
 * it compiles the schema for Postgres.
 *
 * On MySQL/SQLite these columns were range-guarded by the column type itself.
 * On Postgres they degrade to plain `smallint` / `integer` / `bigint`, which
 * accept negative and wildly out-of-range values. That is a data-integrity
 * regression, most seriously for `offers.discount_percent` (a negative discount
 * silently becomes a price *increase*) and `reviews.rating`.
 *
 * This migration restores those guarantees using native CHECK constraints, which
 * PostgreSQL enforces and which also document the intended domain in the schema.
 *
 * Kept as a separate migration rather than editing the original CREATE TABLE
 * statements so that already-deployed databases pick the constraints up cleanly.
 */
return new class extends Migration
{
    /**
     * table.column => CHECK expression
     *
     * @var array<string, string>
     */
    private array $constraints = [
        // Ratings and star classifications
        'reviews.rating' => 'rating >= 0 AND rating <= 5',
        'hotels.stars' => 'stars >= 1 AND stars <= 5',

        // Money — the most dangerous regression if left unguarded
        'offers.discount_percent' => 'discount_percent >= 0 AND discount_percent <= 100',

        // Booking party composition
        'bookings.guests_adults' => 'guests_adults >= 1',
        'bookings.guests_children' => 'guests_children >= 0',

        // Capacity must be positive
        'villas.max_guests' => 'max_guests > 0',
        'villas.bedrooms' => 'bedrooms >= 0',
        'villas.bathrooms' => 'bathrooms >= 0',
        'bed_and_breakfasts.max_guests' => 'max_guests > 0',
        'experiences.included_guests' => 'included_guests >= 1',

        // Ordering and display counters
        'images.order' => '"order" >= 0',

        // Non-negative counters
        'availabilities.quantity' => 'quantity >= 0',
        'destinations.popularity_score' => 'popularity_score >= 0',
        'packages.views' => 'views >= 0',
        'activities.reviews_count' => 'reviews_count >= 0',
        'activities.bookings_count' => 'bookings_count >= 0',
        'accommodations.reviews_count' => 'reviews_count >= 0',
        'hotels.reviews_count' => 'reviews_count >= 0',
        'bed_and_breakfasts.reviews_count' => 'reviews_count >= 0',
        'villas.reviews_count' => 'reviews_count >= 0',
        'packages.reviews_count' => 'reviews_count >= 0',
    ];

    public function up(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->constraints as $target => $expression) {
            [$table, $column] = explode('.', $target);

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            $this->addConstraint($table, $column, $expression);
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'pgsql') {
            return;
        }

        foreach ($this->constraints as $target => $expression) {
            [$table, $column] = explode('.', $target);

            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            DB::statement(sprintf(
                'ALTER TABLE %s DROP CONSTRAINT IF EXISTS %s',
                $this->quote($table),
                $this->quote("chk_{$table}_{$column}")
            ));
        }
    }

    /**
     * Idempotently attach a CHECK constraint.
     *
     * The identifiers are interpolated rather than bound: PostgreSQL does not
     * accept bind parameters inside a `DO $$ ... $$` block, and every value
     * here originates from the constant list above, never from user input.
     */
    private function addConstraint(string $table, string $column, string $expression): void
    {
        $constraint = "chk_{$table}_{$column}";

        $sql = sprintf(
            'DO $$ BEGIN IF NOT EXISTS ('
            .' SELECT 1 FROM pg_constraint WHERE conname = %s'
            .') THEN ALTER TABLE %s ADD CONSTRAINT %s CHECK (%s); END IF; END $$;',
            $this->literal($constraint),
            $this->quote($table),
            $this->quote($constraint),
            $expression
        );

        DB::statement($sql);
    }

    private function quote(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    private function literal(string $value): string
    {
        return "'".str_replace("'", "''", $value)."'";
    }
};
