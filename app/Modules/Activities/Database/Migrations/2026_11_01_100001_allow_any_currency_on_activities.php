<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * An experience is priced in its host's own currency, like every stay: the
 * original enum only allowed USD, EUR, GBP and KES. Keep a three-letter ISO
 * code (the experience wizard validates it against Currencies::codes()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE activities DROP CONSTRAINT IF EXISTS activities_currency_check');
            DB::statement('ALTER TABLE activities ALTER COLUMN currency TYPE varchar(3)');
        } elseif (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE activities MODIFY currency VARCHAR(3) NOT NULL DEFAULT 'USD'");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE activities SET currency = 'USD' WHERE currency NOT IN ('USD', 'EUR', 'GBP', 'KES')");
            DB::statement('ALTER TABLE activities ALTER COLUMN currency TYPE varchar(255)');
            DB::statement("ALTER TABLE activities ADD CONSTRAINT activities_currency_check CHECK (currency::text = ANY (ARRAY['USD', 'EUR', 'GBP', 'KES']::text[]))");
        }
    }
};
