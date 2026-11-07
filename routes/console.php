<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 * Seeds the MVP demo data (DemoSeeder). Safe to run on every deploy: it only
 * seeds when the database has no accounts yet, so redeploying never duplicates
 * the demo or wipes what was created during a showcase. --fresh rebuilds the
 * database from scratch and reseeds it (the deploy workflow's "reset demo").
 */
Artisan::command('demo:seed {--fresh : Drop every table, migrate and reseed the demo}', function () {
    if ($this->option('fresh')) {
        $this->warn('Resetting the demo: dropping all tables and reseeding.');
        $this->call('migrate:fresh', ['--force' => true]);
    } elseif (\App\Modules\Identity\Domain\Models\User::query()->exists()) {
        $this->info('Demo data already present; nothing seeded.');

        return 0;
    }

    $this->call('db:seed', ['--class' => \Database\Seeders\DemoSeeder::class, '--force' => true]);
    $this->info('Demo data seeded.');

    return 0;
})->purpose('Seed the MVP demo data when the database is empty (or --fresh to reset it)');
