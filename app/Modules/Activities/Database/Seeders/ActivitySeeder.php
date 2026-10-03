<?php

namespace App\Modules\Activities\Database\Seeders;

use App\Modules\Activities\Application\Services\ActivityScheduleGenerator;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Database\Seeder;

class ActivitySeeder extends Seeder
{
    public function run(): void
    {
        $destinations = Destination::query()->get();
        if ($destinations->isEmpty()) {
            $this->command?->warn('No destinations found; no sample activities were created.');

            return;
        }

        foreach ($destinations as $destination) {
            Activity::factory()
                ->count(3)
                ->for($destination)
                ->published()
                ->create()
                ->each(function (Activity $activity): void {
                    $schedule = $activity->schedules()->create([
                        'day_of_week' => now()->addDay()->dayOfWeek,
                        'start_time' => '09:00',
                        'end_time' => '12:00',
                        'timezone' => $activity->timezone,
                        'capacity' => $activity->capacity ?: 12,
                        'active_from' => now()->toDateString(),
                        'active_until' => now()->addMonths(6)->toDateString(),
                        'booking_cutoff_minutes' => $activity->booking_cutoff_minutes,
                        'is_active' => true,
                    ]);

                    app(ActivityScheduleGenerator::class)->generate(
                        $schedule,
                        now(),
                        now()->addDays(30)
                    );
                });
        }

        $this->command?->info('Published activity samples and upcoming sessions created.');
    }
}
