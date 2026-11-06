<?php

namespace App\Modules\Packages\Database\Seeders;

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\RoomType;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Administration\Application\Services\TenantVerificationService;
use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Identity\Domain\Models\User;
use App\Modules\Packages\Application\Services\PackageBooker;
use App\Modules\Packages\Application\Services\PackageBuilder;
use App\Modules\Packages\Application\Services\PackagePricing;
use App\Modules\Packages\Application\Services\PackagePublicationService;
use App\Modules\Packages\Domain\Models\Package;
use App\Support\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Throwable;

/**
 * A verified travel agent (agent@holidayhub.test / password) with three live
 * journeys built from the showcase stays and experiences (coast, safari and a
 * mix of both), and a few package bookings so the agent studio has data.
 *
 * Additive and idempotent: packages match on slug, and bookings are only made
 * for a package that has none. Run after the showcase stays and experiences.
 */
class ShowcasePackageSeeder extends Seeder
{
    public const AGENT_EMAIL = 'agent@holidayhub.test';

    public function run(): void
    {
        $agent = $this->agent();
        $admin = User::where('email', 'admin@holidayhub.test')->first();
        $publication = app(PackagePublicationService::class);

        foreach ($this->plans() as $plan) {
            $package = Package::withTrashed()->where('slug', $plan['slug'])->first();

            if (! $package) {
                $package = $this->build($agent, $plan);
                if (! $package) {
                    $this->say("Skipped {$plan['slug']}: its listings aren't seeded.");

                    continue;
                }
            }

            try {
                if (in_array($package->status, ['draft', 'rejected'], true)) {
                    $package = $publication->submit($package->fresh(), $agent->user);
                }
                if ($package->status === 'pending_review') {
                    $package = $publication->approve($package->fresh(), $admin);
                }
            } catch (Throwable $e) {
                $this->say("{$plan['slug']} not published: ".$e->getMessage());

                continue;
            }

            app(PackageBuilder::class)->refreshFromPrice($package);
            $this->bookings($package->fresh(), $plan['bookings']);
        }
    }

    private function agent(): Agent
    {
        $user = User::firstOrCreate(
            ['email' => self::AGENT_EMAIL],
            ['name' => 'Amina Odhiambo', 'password' => Hash::make('password'), 'email_verified_at' => now()]
        );
        $user->assignRole(Role::firstOrCreate(['name' => 'agent', 'guard_name' => 'web']));

        $agent = Agent::withTrashed()->firstOrNew(['user_id' => $user->id]);
        $agent->fill([
            'first_name' => 'Amina',
            'last_name' => 'Odhiambo',
            'email' => self::AGENT_EMAIL,
            'agency_name' => 'Tembea Journeys',
            'agency_license' => 'KATA 0421',
            'business_type' => 'agency',
            'specialization' => 'Coast and safari journeys',
            'country' => 'Kenya',
            'city' => 'Mombasa',
            'bio' => 'Twelve years putting East Africa together for families who want the wild and the beach in one trip.',
            'commission_rate' => 12,
            'active' => true,
        ]);
        if ($agent->trashed()) {
            $agent->restore();
        }
        $agent->save();

        // Through the verification workflow when it is available, so the
        // admin panel shows an approved agent; otherwise flag it directly.
        try {
            $service = app(TenantVerificationService::class);
            $tenant = $service->apply($user, $agent, 'agent', $agent->agency_name, $agent->email);
            $admin = User::where('email', 'admin@holidayhub.test')->first();
            if ($admin && $tenant->status->value !== 'approved') {
                if ($tenant->status->value === 'pending') {
                    $tenant = $service->startReview($tenant, $admin);
                }
                $service->approve($tenant, $admin, 'Showcase agent.');
            }
        } catch (Throwable $e) {
            $this->say('Agent verified directly: '.$e->getMessage());
        }

        $agent->refresh();
        if (! $agent->isVerified()) {
            $agent->markVerified();
            $agent->save();
        }

        return $agent->load('user');
    }

    /**
     * Three journeys from the showcase listings. Days: stays run from their
     * day for `nights`; experiences happen on their day.
     */
    private function plans(): array
    {
        return [
            [
                'slug' => 'swahili-coast-slow-week',
                'name' => 'Swahili Coast Slow Week',
                'hook' => 'Reefs in Watamu, a dhow at sunset off Lamu, and nothing to do on the last day but swim.',
                'story' => "Five days down the Kenyan coast at the pace it asks for. You start in a whitewashed villa above Watamu's turquoise water, snorkel the marine park the next morning, then fly north to Lamu, where there are no cars and the dhows still sail at dusk.\n\nTwo nights in an old-town courtyard B&B, a sunset sail with fresh juice and grilled snacks, and a last day that is entirely yours.",
                'destination' => 'watamu',
                'also' => ['lamu-island'],
                'days' => 5,
                'currency' => 'USD',
                'markup' => ['percent', 12],
                'guests' => [1, 6],
                'components' => [
                    ['day' => 1, 'kind' => 'stay', 'listing' => [Villa::class, 'Watamu White House'], 'nights' => 2],
                    ['day' => 2, 'kind' => 'experience', 'listing' => [Activity::class, 'Watamu Marine Park Snorkel & Reef Dive']],
                    ['day' => 3, 'kind' => 'transfer', 'title' => 'Flight and boat transfer to Lamu', 'price' => 140, 'description' => 'Malindi to Manda by light aircraft, then the boat across to Lamu town.'],
                    ['day' => 3, 'kind' => 'stay', 'listing' => [BedAndBreakfast::class, 'Old Town Courtyard B&B'], 'nights' => 2],
                    ['day' => 4, 'kind' => 'experience', 'listing' => [Activity::class, 'Sunset Dhow Sail off Lamu']],
                    ['day' => 5, 'kind' => 'free', 'title' => 'A morning on Shela beach', 'description' => 'Walk the dunes, swim, and catch the afternoon boat back to Manda.'],
                ],
                'inclusions' => ['Your agent on call 24/7', 'Local SIM card on arrival'],
                'exclusions' => ['International flights', 'Tips for guides and crew'],
                'bookings' => 2,
            ],
            [
                'slug' => 'mara-to-kilimanjaro-safari',
                'name' => 'Mara to Kilimanjaro Safari',
                'hook' => 'A balloon over the Mara at dawn, then elephants beneath Kilimanjaro: two great parks in five days.',
                'story' => "The classic Kenyan safari, without the convoy. Two nights at a lodge on the Mara escarpment, with a hot-air balloon flight at first light and a bush breakfast where you land.\n\nThen south to Amboseli, where a family-run B&B sits among acacias and the morning game drive puts you face to face with the herds under Kilimanjaro.",
                'destination' => 'maasai-mara',
                'also' => ['amboseli'],
                'days' => 5,
                'currency' => 'KES',
                'markup' => ['fixed', 18000],
                'guests' => [2, 4],
                'components' => [
                    ['day' => 1, 'kind' => 'stay', 'listing' => [RoomType::class, 'Escarpment Room', 'Mara Ridge Safari Lodge'], 'nights' => 2],
                    ['day' => 2, 'kind' => 'experience', 'listing' => [Activity::class, 'Hot-Air Balloon Safari over the Mara']],
                    ['day' => 3, 'kind' => 'transfer', 'title' => 'Bush flight Mara to Amboseli', 'price' => 32000, 'description' => 'Scheduled light-aircraft flight via Wilson, about three hours.'],
                    ['day' => 3, 'kind' => 'stay', 'listing' => [BedAndBreakfast::class, 'Kimana Acacia B&B'], 'nights' => 2],
                    ['day' => 4, 'kind' => 'experience', 'listing' => [Activity::class, 'Amboseli Game Drive Beneath Kilimanjaro']],
                    ['day' => 5, 'kind' => 'note', 'title' => 'Pack light', 'description' => 'Bush planes take 15 kg in soft bags.'],
                ],
                'inclusions' => ['Park fees for the game drive', 'Your agent on call 24/7'],
                'exclusions' => ['International flights', 'Visas'],
                'bookings' => 2,
            ],
            [
                'slug' => 'bush-and-beach-in-five-days',
                'name' => 'Bush & Beach in Five Days',
                'hook' => 'Swahili cooking in Diani, then the reef and the creek at Watamu: the coast, two ways.',
                'story' => "For people who want the beach but not only the beach. Start on Diani's long white sand with an Ocean King room and a Swahili home cooking class, then head north to a lodge on Mida Creek.\n\nThe last full day is out on the reef with a marine-park guide.",
                'destination' => 'diani-beach',
                'also' => ['watamu'],
                'days' => 5,
                'currency' => 'EUR',
                'markup' => ['percent', 10],
                'guests' => [1, 4],
                'components' => [
                    ['day' => 1, 'kind' => 'stay', 'listing' => [RoomType::class, 'Ocean King', 'Coral Tide Beach Hotel'], 'nights' => 2],
                    ['day' => 2, 'kind' => 'experience', 'listing' => [Activity::class, 'Swahili Home Cooking Class in Diani']],
                    ['day' => 3, 'kind' => 'transfer', 'title' => 'Coast road to Watamu', 'price' => 95, 'description' => 'Private air-conditioned car, about three and a half hours.'],
                    ['day' => 3, 'kind' => 'stay', 'listing' => [RoomType::class, 'Lodge Room', 'Mida Creek Ocean Lodge'], 'nights' => 2],
                    ['day' => 4, 'kind' => 'experience', 'listing' => [Activity::class, 'Watamu Marine Park Snorkel & Reef Dive']],
                    ['day' => 5, 'kind' => 'free', 'title' => 'Slow morning on the creek'],
                ],
                'inclusions' => ['Your agent on call 24/7'],
                'exclusions' => ['International flights', 'Drinks with meals'],
                'bookings' => 1,
            ],
        ];
    }

    private function build(Agent $agent, array $plan): ?Package
    {
        $components = [];
        foreach ($plan['components'] as $i => $row) {
            $listing = isset($row['listing']) ? $this->listing($row['listing']) : null;
            if (isset($row['listing']) && ! $listing) {
                return null;
            }

            $components[] = [
                'day' => $row['day'],
                'sequence' => $i,
                'kind' => $row['kind'],
                'componentable_type' => $listing?->getMorphClass(),
                'componentable_id' => $listing?->getKey(),
                'nights' => $row['nights'] ?? null,
                'quantity' => 1,
                'title' => $row['title'] ?? ($listing instanceof RoomType ? $listing->hotel?->name.' · '.$listing->name : $listing?->name),
                'description' => $row['description'] ?? null,
                'price' => $row['price'] ?? null,
                'currency' => isset($row['price']) ? $plan['currency'] : null,
            ];
        }

        $destination = Destination::where('slug', $plan['destination'])->first();
        $package = Package::create([
            'name' => $plan['name'],
            'slug' => $plan['slug'],
            'short_description' => $plan['hook'],
            'full_description' => $plan['story'],
            'destination_id' => $destination?->id,
            'agent_id' => $agent->id,
            'base_price' => 0,
            'currency' => $plan['currency'],
            'duration_days' => $plan['days'],
            'duration_nights' => $plan['days'] - 1,
            'markup_type' => $plan['markup'][0],
            'markup_value' => $plan['markup'][1],
            'min_guests' => $plan['guests'][0],
            'max_guests' => $plan['guests'][1],
            'inclusions' => $plan['inclusions'],
            'exclusions' => $plan['exclusions'],
            'status' => 'draft',
            'active' => false,
            'is_featured' => true,
            'meta_data' => ['destinations' => Destination::whereIn('slug', $plan['also'])->pluck('id')->all()],
        ]);
        $package->components()->createMany($components);

        // Photos, films and the journey reel, borrowed from the listings inside.
        $media = app(PackageBuilder::class)->listingMedia($package->fresh());
        $package->forceFill([
            'cover_image' => $media['cover'],
            'cover_video' => $media['film'],
            'gallery' => $media['gallery'],
        ])->saveQuietly();

        $stops = ['arrival', 'the-stay', 'experience', 'on-the-road', 'the-stay', 'experience', 'golden-hour', 'farewell'];
        collect([$media['film'], $media['cover']])->concat($media['gallery'])->filter()->unique()->take(6)->values()
            ->each(fn ($path, $i) => $package->scenes()->create([
                'position' => $i + 1,
                'space' => $stops[$i] ?? null,
                'title' => $i === 0 ? 'Where it begins' : null,
                'media_path' => $path,
                'media_type' => Media::isVideo($path) ? 'video' : 'photo',
            ]));

        return $package->fresh();
    }

    private function listing(array $spec)
    {
        [$class, $name] = $spec;

        if ($class === RoomType::class) {
            $hotel = Hotel::where('name', $spec[2])->first();

            return $hotel ? RoomType::where('hotel_id', $hotel->id)->where('name', $name)->first() : null;
        }

        return $class::where('name', $name)->first();
    }

    /** A few bookings by showcase travellers, on the next departures that are free. */
    private function bookings(Package $package, int $count): void
    {
        if ($package->bookings()->exists() || ! $package->isPublished()) {
            return;
        }

        $guests = User::whereHas('guest')->where('email', '!=', self::AGENT_EMAIL)
            ->orderByRaw("CASE WHEN email = 'demo@holidayhub.test' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->limit(5)
            ->get();
        $party = max(2, (int) $package->min_guests);
        $departures = app(PackagePricing::class)->nextDepartures($package, $party, $count + 2);
        $made = 0;

        foreach ($departures as $i => $departure) {
            $guest = $guests[($i + crc32($package->slug)) % max(1, $guests->count())] ?? null;
            if (! $guest || $made >= $count) {
                break;
            }

            try {
                app(PackageBooker::class)->book($package->fresh(), $guest, $departure['date'], $party, (string) Str::uuid());
                $made++;
            } catch (Throwable $e) {
                $this->say("Booking for {$package->slug} on {$departure['date']} skipped: ".$e->getMessage());
            }
        }

        $this->say("{$package->name}: {$made} package ".Str::plural('booking', $made).'.');
    }

    private function say(string $line): void
    {
        $this->command ? $this->command->line($line) : fwrite(STDERR, $line.PHP_EOL);
    }
}
