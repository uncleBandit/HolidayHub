<?php

namespace App\Modules\Activities\Application\Services;

use App\Modules\Activities\Domain\Models\ActivitySchedule;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ActivityScheduleGenerator
{
    /**
     * @return Collection<int, \App\Modules\Activities\Domain\Models\ActivitySession>
     */
    public function generate(ActivitySchedule $schedule, CarbonInterface $from, CarbonInterface $through): Collection
    {
        if (! $schedule->is_active || $through->lt($from)) {
            return collect();
        }

        if (! in_array($schedule->day_of_week, range(0, 6), true)) {
            throw new InvalidArgumentException('Schedule day_of_week must be between 0 (Sunday) and 6 (Saturday).');
        }

        $timezone = $schedule->timezone;
        $localFrom = Carbon::parse($from)->setTimezone($timezone)->startOfDay();
        $localThrough = Carbon::parse($through)->setTimezone($timezone)->startOfDay();
        $sessions = collect();

        for ($day = $localFrom->copy(); $day->lte($localThrough); $day->addDay()) {
            if ($day->dayOfWeek !== $schedule->day_of_week) {
                continue;
            }

            if ($schedule->active_from && $day->lt($schedule->active_from->startOfDay())) {
                continue;
            }

            if ($schedule->active_until && $day->gt($schedule->active_until->endOfDay())) {
                continue;
            }

            $startsAt = Carbon::parse($day->toDateString().' '.$schedule->start_time, $timezone);
            $endsAt = Carbon::parse($day->toDateString().' '.$schedule->end_time, $timezone);
            if ($endsAt->lte($startsAt)) {
                $endsAt->addDay();
            }

            $cutoffMinutes = $schedule->booking_cutoff_minutes ?? $schedule->activity->booking_cutoff_minutes;
            $bookingCutoff = $cutoffMinutes === null
                ? null
                : $startsAt->copy()->subMinutes($cutoffMinutes)->utc();

            $session = $schedule->sessions()->firstOrCreate(
                ['starts_at' => $startsAt->copy()->utc()],
                [
                    'activity_id' => $schedule->activity_id,
                    'activity_option_id' => $schedule->activity_option_id,
                    'ends_at' => $endsAt->copy()->utc(),
                    'timezone' => $timezone,
                    'capacity' => $schedule->capacity,
                    'booked_capacity' => 0,
                    'status' => 'scheduled',
                    'booking_cutoff_at' => $bookingCutoff,
                ]
            );

            $sessions->push($session);
        }

        return $sessions;
    }
}
