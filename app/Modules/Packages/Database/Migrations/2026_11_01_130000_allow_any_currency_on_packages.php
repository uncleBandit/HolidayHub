<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * A package is priced in whatever currency its agent sells in (the listings
 * inside are converted into it), so the original four-currency enum goes:
 * keep a three-letter ISO code, validated against Currencies::codes() by the
 * builder. base_price becomes the cached "from" price of a live quote, so a
 * fresh draft starts at 0 instead of failing NOT NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE packages DROP CONSTRAINT IF EXISTS packages_currency_check');
            DB::statement('ALTER TABLE packages ALTER COLUMN currency TYPE varchar(3)');
            DB::statement("ALTER TABLE packages ALTER COLUMN currency SET DEFAULT 'USD'");
            DB::statement('ALTER TABLE packages ALTER COLUMN base_price SET DEFAULT 0');
        } elseif (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE packages MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'USD'");
            DB::statement('ALTER TABLE packages MODIFY base_price DECIMAL(10,2) NOT NULL DEFAULT 0');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE packages SET currency = 'USD' WHERE currency NOT IN ('USD', 'EUR', 'GBP', 'KES')");
            DB::statement('ALTER TABLE packages ALTER COLUMN base_price DROP DEFAULT');
            DB::statement('ALTER TABLE packages ALTER COLUMN currency TYPE varchar(255)');
            DB::statement("ALTER TABLE packages ADD CONSTRAINT packages_currency_check CHECK (currency::text = ANY (ARRAY['USD', 'EUR', 'GBP', 'KES']::text[]))");
        }
    }
};
