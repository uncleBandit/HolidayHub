<?php

namespace App\Modules\Administration\Application\Services;

use App\Modules\Administration\Domain\Models\PlatformSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Platform-wide settings, edited by admins on the "Platform settings" page.
 *
 * Every setting has a default here, so the app behaves the same before an
 * admin has saved anything. Values are cached as one array and the cache is
 * dropped on every save, so a change applies on the next request.
 *
 *   app(PlatformSettings::class)->get('support.email')
 */
class PlatformSettings
{
    private const CACHE_KEY = 'platform-settings';

    /** @var array<string, mixed> */
    public const DEFAULTS = [
        'support.email' => 'support@holidayhub.test',
        'support.phone' => null,

        // A notice across the top of every guest page (maintenance, holidays, …).
        'banner.enabled' => false,
        'banner.message' => null,
        'banner.tone' => 'info', // info | warning

        // Used for listings whose host has not chosen a cancellation policy.
        'bookings.default_cancellation' => 'Free cancellation up to 48 hours before the start.',
        // Periods when guests cannot cancel online: [{start, end, label}].
        'bookings.blackouts' => [],

        // Commission (%) a newly approved travel agent starts on.
        'agents.default_commission' => 10,
    ];

    /** @var array<string, mixed>|null */
    private ?array $values = null;

    public function get(string $key, mixed $default = null): mixed
    {
        $values = $this->all();

        return array_key_exists($key, $values) ? $values[$key] : $default;
    }

    /**
     * Every setting, saved values over defaults.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->values ??= Cache::rememberForever(self::CACHE_KEY, function (): array {
            // Before the table exists (fresh install, early migrations) use the defaults.
            if (! Schema::hasTable('platform_settings')) {
                return self::DEFAULTS;
            }

            $saved = PlatformSetting::query()->pluck('value', 'key')->all();

            return array_merge(self::DEFAULTS, Arr::only($saved, array_keys(self::DEFAULTS)));
        });
    }

    /**
     * Save several settings at once and return what changed, as
     * [key => [before, after]], so the caller can audit it.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    public function save(array $values, ?int $userId = null): array
    {
        $current = $this->all();
        $changes = [];

        DB::transaction(function () use ($values, $current, $userId, &$changes): void {
            foreach (Arr::only($values, array_keys(self::DEFAULTS)) as $key => $value) {
                if ($current[$key] === $value) {
                    continue;
                }

                PlatformSetting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'updated_by' => $userId]);
                $changes[$key] = [$current[$key], $value];
            }
        });

        Cache::forget(self::CACHE_KEY);
        $this->values = null;

        return $changes;
    }
}
