<?php

namespace App\Console\Commands;

use App\Support\ModuleRegistry;
use Illuminate\Console\Command;
use Illuminate\Database\Seeder;

/**
 * Seeds the seeders owned by a single module.
 *
 * `php artisan db:seed` runs DatabaseSeeder, which sequences every module in
 * dependency order. That is the correct path for a full database build, but it
 * gives you no way to re-seed one bounded context in isolation while working on
 * it — which is the common case in a modular monolith.
 *
 * This command reads ownership from ModuleRegistry rather than from a hardcoded
 * list, so a new module is seedable the moment its Database/Seeders directory
 * exists, with no edits here.
 */
class ModuleSeedCommand extends Command
{
    protected $signature = 'module:seed
                            {module? : Module id to seed. Omit to list the modules that have seeders.}
                            {--force : Run without confirmation in production.}';

    protected $description = 'Seed the database seeders owned by a single module';

    public function handle(ModuleRegistry $registry): int
    {
        $seeders = $registry->seedersByModule();

        if ($this->argument('module') === null) {
            $this->listModules($registry, $seeders);

            return self::SUCCESS;
        }

        $id = (string) $this->argument('module');

        if (! $registry->has($id)) {
            $this->components->error("Unknown module [{$id}].");

            return self::FAILURE;
        }

        if (! isset($seeders[$id])) {
            $this->components->error("Module [{$id}] owns no seeders.");

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            'Seeding module [%s]: %d seeder(s).',
            $id,
            count($seeders[$id]),
        ));

        foreach ($seeders[$id] as $seeder) {
            $this->components->task(class_basename($seeder), function () use ($seeder): bool {
                /** @var Seeder $instance */
                $instance = $this->laravel->make($seeder);
                $instance->setContainer($this->laravel)->setCommand($this);

                $instance->__invoke();

                return true;
            });
        }

        $this->components->info("Done. Seeded [{$id}].");

        $this->line('Cross-module data is <comment>not</comment> created by this command. '
            .'Seeders that need rows from another module skip themselves when those rows are absent, '
            .'so run <info>php artisan db:seed</info> for a fully populated database.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, array<int, class-string>>  $seeders
     */
    private function listModules(ModuleRegistry $registry, array $seeders): void
    {
        if ($seeders === []) {
            $this->components->warn('No module owns any seeders.');

            return;
        }

        $this->components->info('Modules with seeders:');
        $this->table(
            ['Module', 'Seeders', 'Order source'],
            array_map(
                static fn (string $id, array $classes): array => [
                    $id,
                    implode(', ', array_map(
                        static fn (string $class): string => class_basename($class),
                        $classes,
                    )),
                    $registry->get($id)?->manifest['seeders'] ?? null ? 'module.json' : 'alphabetical',
                ],
                array_keys($seeders),
                array_values($seeders),
            ),
        );

        $this->line('Run <info>php artisan module:seed <module-id></info> to seed one module.');
    }
}
