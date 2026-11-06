<?php

namespace App\Modules\Activities\Database\Seeders;

use App\Modules\Activities\Application\Services\ActivityPublicationService;
use App\Modules\Activities\Application\Services\ActivityScheduleGenerator;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Destinations\Database\Seeders\ShowcaseDestinationSeeder;
use App\Modules\Identity\Database\Seeders\ShowcaseAccountSeeder;
use App\Modules\Providers\Database\Seeders\ShowcaseProviderSeeder;
use Illuminate\Database\Seeder;

/**
 * Published showcase experiences tied to the showcase destinations.
 *
 * Each activity goes through ActivityPublicationService
 * (submit -> approve -> publish), so the final row is genuinely
 * Published/Approved/active and has the verification history an admin
 * review leaves behind. Weekly schedules generate real future sessions, so
 * the booking flow has dates to offer. Prices are KES per person.
 *
 * Idempotent: activities match on slug and are only saved when a listing
 * field actually changed (a change demotes a published activity to draft via
 * the model's updating hook, after which it is re-published here). Schedules
 * match on weekday + start time; sessions on start time.
 */
class ShowcaseExperienceSeeder extends Seeder
{
    /** Category slugs match ExperienceSeeder / config/experiences.php. */
    private const CATEGORIES = [
        'safari-wildlife' => ['name' => 'Safari & Wildlife', 'sort_order' => 1],
        'ocean-water' => ['name' => 'Ocean & Water', 'sort_order' => 2],
        'adventure-trekking' => ['name' => 'Adventure & Trekking', 'sort_order' => 3],
        'culture-heritage' => ['name' => 'Culture & Heritage', 'sort_order' => 4],
        'food-markets' => ['name' => 'Food & Markets', 'sort_order' => 5],
    ];

    public const EXPERIENCES = [
        'sunset-dhow-sail-lamu' => [
            'provider' => 'coast',
            'destination' => 'lamu-island',
            'category' => 'ocean-water',
            'name' => 'Sunset Dhow Sail off Lamu',
            'place' => 'Lamu Seafront Jetty',
            'short_description' => 'Three hours under a lateen sail as the sun drops into the Lamu channel, with fresh juice and grilled snacks on board.',
            'description' => 'Board a traditional wooden dhow at the Lamu seafront and sail down the channel past Shela towards the open sea. The crew raise the big lateen sail, cut the engine and let the wind do the work while the sun sets over the mangroves. Fresh juice, coconut and grilled seafood snacks are served on deck; swimming stop at Shela if the tide allows.',
            'thumbnail' => '1664093671658-a2aac3a344a9',
            'gallery' => [],
            'duration' => 180,
            'price' => 6500,
            'capacity' => 12,
            'min_age' => 4,
            'rating' => 4.8,
            'reviews' => 146,
            'featured' => true,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[2, '16:00'], [5, '16:00'], [6, '16:00']],
            'highlights' => ['Traditional lateen-sail dhow', 'Sunset over the Lamu channel', 'Swim stop at Shela', 'Seafood snacks on board'],
            'inclusions' => ['Captain and crew', 'Fresh juice and water', 'Grilled snacks', 'Life jackets'],
            'tags' => ['sunset', 'sailing', 'culture', 'couples'],
            'weather' => true,
        ],
        'amboseli-game-drive-kilimanjaro' => [
            'provider' => 'safari',
            'destination' => 'amboseli',
            'category' => 'safari-wildlife',
            'name' => 'Amboseli Game Drive Beneath Kilimanjaro',
            'place' => 'Amboseli Kimana Gate',
            'short_description' => 'A dawn drive among Amboseli’s elephant herds while Kilimanjaro is still clear of cloud.',
            'description' => 'Leave the gate at first light, when Kilimanjaro is most likely to be clear, and spend the morning with Amboseli’s famous big-tusked elephant families as they walk out to the swamps. Your guide drives a pop-top 4x4, knows the herds by name and times the stops for the light. Packed breakfast on Observation Hill.',
            'thumbnail' => '1585402969048-86f38f8bfc2e',
            'gallery' => ['1489392191049-fc10c97e64b6', '1557178985-891ca9b9b01c'],
            'duration' => 300,
            'price' => 14500,
            'capacity' => 6,
            'min_age' => 5,
            'rating' => 4.9,
            'reviews' => 228,
            'featured' => true,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[1, '06:00'], [3, '06:00'], [5, '06:00'], [6, '06:00']],
            'highlights' => ['Elephant herds at the swamps', 'Kilimanjaro views at dawn', 'Pop-top 4x4 with expert guide', 'Breakfast on Observation Hill'],
            'inclusions' => ['Park-certified guide', 'Pop-top 4x4', 'Packed breakfast', 'Bottled water', 'Binoculars'],
            'tags' => ['wildlife', 'elephants', 'sunrise', 'photography'],
            'weather' => false,
        ],
        'mount-kenya-moorland-day-hike' => [
            'provider' => 'safari',
            'destination' => 'mount-kenya',
            'category' => 'adventure-trekking',
            'name' => 'Mount Kenya Moorland Day Hike',
            'place' => 'Sirimon Gate, Mount Kenya',
            'short_description' => 'Forest to giant groundsel moorland on the Sirimon route, with a mountain guide and a picnic lunch at 3,300 m.',
            'description' => 'A full day on the northern slopes of Mount Kenya. Start at the Sirimon gate, climb through cedar and podocarpus forest into open moorland of tussock grass and giant groundsels, and stop for lunch with views up towards the peaks. A steady, non-technical hike of around 14 km for reasonably fit walkers, led by a KWS-certified mountain guide.',
            'thumbnail' => '1707819487421-21228af983a9',
            'gallery' => ['1551632811-561732d1e306'],
            'duration' => 480,
            'price' => 9800,
            'capacity' => 10,
            'min_age' => 12,
            'rating' => 4.7,
            'reviews' => 87,
            'featured' => false,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[3, '07:00'], [6, '07:00']],
            'highlights' => ['Forest-to-moorland ascent', 'Giant groundsels and lobelias', 'Picnic lunch at 3,300 m', 'Certified mountain guide'],
            'inclusions' => ['Park fees', 'Mountain guide', 'Picnic lunch', 'Transfers from Nanyuki'],
            'tags' => ['hiking', 'mountains', 'adventure'],
            'weather' => true,
        ],
        'marrakech-medina-souk-walk' => [
            'provider' => 'world',
            'destination' => 'marrakech-sahara',
            'category' => 'culture-heritage',
            'name' => 'Marrakech Medina & Souk Walk',
            'place' => 'Koutoubia Mosque Gardens',
            'short_description' => 'From the Koutoubia minaret into the souks, with a local guide who knows which doors to knock on.',
            'description' => 'Meet beneath the Koutoubia minaret, with the Atlas mountains on the horizon on a clear morning, then walk into the medina. Your guide leads you through the dyers’, spice and metalworkers’ souks, into a hidden riad courtyard and a 16th-century madrasa, and finishes with mint tea above Jemaa el-Fnaa.',
            'thumbnail' => '1597212618440-806262de4f6b',
            'gallery' => [],
            'duration' => 180,
            'price' => 5200,
            'capacity' => 10,
            'min_age' => 6,
            'rating' => 4.6,
            'reviews' => 312,
            'featured' => false,
            'timezone' => 'Africa/Casablanca',
            'slots' => [[1, '09:30'], [3, '09:30'], [5, '09:30'], [0, '09:30']],
            'highlights' => ['Koutoubia minaret', 'Dyers’ and spice souks', 'Hidden riad courtyard', 'Mint tea over Jemaa el-Fnaa'],
            'inclusions' => ['Licensed local guide', 'Madrasa entry', 'Mint tea'],
            'tags' => ['culture', 'markets', 'walking', 'history'],
            'weather' => false,
        ],
        'watamu-marine-park-snorkel-dive' => [
            'provider' => 'coast',
            'destination' => 'watamu',
            'category' => 'ocean-water',
            'name' => 'Watamu Marine Park Snorkel & Reef Dive',
            'place' => 'Watamu Marine Park Jetty',
            'short_description' => 'Glass-bottom boat out to the coral gardens, then snorkel — or take a guided reef dive with a PADI instructor.',
            'description' => 'A morning on Watamu’s protected reef. A glass-bottom boat takes you out to the coral gardens, where shoals of reef fish gather around the coral heads. Snorkellers stay with a guide at the surface; certified divers (or first-timers on a supervised discover-scuba dive) go down with a PADI instructor. Turtles are often seen.',
            'thumbnail' => '1544551763-46a013bb70d5',
            'gallery' => ['1651860282131-e3257674ccd1'],
            'duration' => 210,
            'price' => 7500,
            'capacity' => 8,
            'min_age' => 10,
            'rating' => 4.8,
            'reviews' => 194,
            'featured' => true,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[2, '08:30'], [4, '08:30'], [6, '08:30']],
            'highlights' => ['Protected coral gardens', 'Shoals of reef fish', 'Snorkel or guided reef dive', 'Glass-bottom boat'],
            'inclusions' => ['Marine park fees', 'Snorkel gear', 'PADI instructor for divers', 'Fruit and water'],
            'tags' => ['snorkelling', 'diving', 'reef', 'marine life'],
            'weather' => true,
        ],
        'mara-hot-air-balloon-safari' => [
            'provider' => 'safari',
            'destination' => 'maasai-mara',
            'category' => 'safari-wildlife',
            'name' => 'Hot-Air Balloon Safari over the Mara',
            'place' => 'Mara Ridge Launch Site',
            'short_description' => 'Lift off at dawn and drift over the plains for an hour, then land to a champagne bush breakfast.',
            'description' => 'Pick-up is in the dark; the balloons inflate as the sky turns pink and you lift off at sunrise. For about an hour the pilot follows the wind low over the Mara’s rivers and plains, often over herds of wildebeest, elephants and giraffe. After landing there is a champagne breakfast in the bush and a game drive back to camp.',
            'thumbnail' => '1507608616759-54f48f0af0ee',
            'gallery' => ['1516426122078-c23e76319801'],
            'duration' => 240,
            'price' => 52000,
            'capacity' => 16,
            'min_age' => 7,
            'rating' => 4.9,
            'reviews' => 176,
            'featured' => true,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[0, '05:30'], [2, '05:30'], [4, '05:30'], [6, '05:30']],
            'highlights' => ['Sunrise lift-off', 'One-hour flight over the plains', 'Champagne bush breakfast', 'Flight certificate'],
            'inclusions' => ['Balloon flight', 'Bush breakfast', 'Camp transfers', 'Return game drive'],
            'tags' => ['balloon', 'sunrise', 'wildlife', 'bucket list'],
            'weather' => true,
        ],
        'kyoto-tea-ceremony-matcha' => [
            'provider' => 'world',
            'destination' => 'kyoto',
            'category' => 'culture-heritage',
            'name' => 'Kyoto Tea Ceremony & Matcha Tasting',
            'place' => 'Higashiyama Tea House',
            'short_description' => 'A traditional tea ceremony in a Higashiyama tea house, then whisk your own bowl and finish with a matcha latte.',
            'description' => 'In a quiet tea house a few lanes from the Yasaka pagoda, a tea master guides you through the chado ceremony: the ritual, the sweets, and how to receive the bowl. Then you whisk your own matcha, learn to tell ceremonial from culinary grades, and finish in the tea house café with a matcha latte. Kimono rental can be added.',
            'thumbnail' => '1515823064-d6e0c04616a7',
            'gallery' => ['1493976040374-85c8e12f0c0e', '1545569341-9eb8b30979d9'],
            'duration' => 90,
            'price' => 6800,
            'capacity' => 8,
            'min_age' => 8,
            'rating' => 4.8,
            'reviews' => 403,
            'featured' => false,
            'timezone' => 'Asia/Tokyo',
            'slots' => [[1, '10:00'], [3, '14:00'], [5, '10:00'], [6, '14:00']],
            'highlights' => ['Ceremony led by a tea master', 'Whisk your own matcha', 'Seasonal wagashi sweets', 'Matcha latte to finish'],
            'inclusions' => ['Tea ceremony', 'Matcha and sweets', 'Matcha latte', 'English-speaking host'],
            'tags' => ['tea', 'culture', 'kyoto', 'slow travel'],
            'weather' => false,
        ],
        'swahili-home-cooking-class-diani' => [
            'provider' => 'coast',
            'destination' => 'diani-beach',
            'category' => 'food-markets',
            'name' => 'Swahili Home Cooking Class in Diani',
            'place' => 'Ukunda Market',
            'short_description' => 'Shop the Ukunda market with your host, then cook coconut fish curry, pilau and chapati in her home kitchen.',
            'description' => 'Start at Ukunda market choosing fish, spices and coconuts with your host, then head to her bright home kitchen to cook a Swahili coastal lunch together: fish in coconut curry, spiced pilau, kachumbari and soft, flaky chapati. Everyone gets hands-on at the stove, and you sit down to eat what you cooked. Recipes to take home.',
            'thumbnail' => '1556910103-1c02745aae4d',
            'gallery' => ['1556909114-f6e7ad7d3136'],
            'duration' => 180,
            'price' => 5800,
            'capacity' => 8,
            'min_age' => 8,
            'rating' => 4.7,
            'reviews' => 118,
            'featured' => false,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[2, '10:00'], [4, '10:00'], [6, '10:00']],
            'highlights' => ['Market shopping with your host', 'Hands-on in a home kitchen', 'Coconut fish curry and pilau', 'Recipes to take home'],
            'inclusions' => ['All ingredients', 'Lunch', 'Soft drinks', 'Recipe cards'],
            'tags' => ['food', 'cooking', 'family', 'local'],
            'weather' => false,
        ],
        'sahara-camel-trek-desert-camp' => [
            'provider' => 'world',
            'destination' => 'marrakech-sahara',
            'category' => 'adventure-trekking',
            'name' => 'Sahara Camel Trek & Desert Camp Night',
            'place' => 'Merzouga, Erg Chebbi',
            'short_description' => 'Ride into the Erg Chebbi dunes at sunset, sleep in a lantern-lit camp and walk up a dune for sunrise.',
            'description' => 'Meet the camels at the edge of Merzouga in the late afternoon and ride into the orange dunes of Erg Chebbi as the light softens. Dinner is tagine around the fire at a lantern-lit desert camp, followed by drumming and stargazing. Wake before dawn to climb a dune for sunrise, then ride back for breakfast.',
            'thumbnail' => '1559586616-361e18714958',
            'gallery' => ['1613169620329-6785c004d900'],
            'duration' => 1080,
            'price' => 24000,
            'capacity' => 12,
            'min_age' => 6,
            'rating' => 4.8,
            'reviews' => 265,
            'featured' => true,
            'timezone' => 'Africa/Casablanca',
            'slots' => [[1, '16:00'], [4, '16:00'], [6, '16:00']],
            'highlights' => ['Sunset camel ride', 'Night in a desert camp', 'Stargazing in the Sahara', 'Dune-top sunrise'],
            'inclusions' => ['Camel and guide', 'Tent', 'Dinner and breakfast', 'Water'],
            'tags' => ['desert', 'camels', 'stars', 'adventure'],
            'weather' => true,
        ],
        'tsavo-east-sundowner-drive' => [
            'provider' => 'safari',
            'destination' => 'tsavo',
            'category' => 'safari-wildlife',
            'name' => 'Tsavo East Sundowner Game Drive',
            'place' => 'Voi Gate, Tsavo East',
            'short_description' => 'An afternoon drive among Tsavo’s red-dusted elephants, ending with a sundowner under the acacias.',
            'description' => 'Tsavo East is vast and uncrowded, and the late afternoon is when it comes alive. Head out from Voi gate in an open 4x4 to find elephants coloured red by the soil, giraffe browsing the acacias and lions resting near the waterholes, then stop on a rise for a sundowner as the sun drops behind the trees.',
            'thumbnail' => '1535941339077-2dd1c7963098',
            'gallery' => ['1547471080-7cc2caa01a7e', '1523805009345-7448845a9e53'],
            'duration' => 240,
            'price' => 11200,
            'capacity' => 6,
            'min_age' => 5,
            'rating' => 4.6,
            'reviews' => 92,
            'featured' => false,
            'timezone' => 'Africa/Nairobi',
            'slots' => [[2, '15:00'], [5, '15:00'], [0, '15:00']],
            'highlights' => ['Red elephants of Tsavo', 'Uncrowded game viewing', 'Sundowner under the acacias', 'Open 4x4'],
            'inclusions' => ['Park fees', 'Guide and 4x4', 'Sundowner drinks and snacks'],
            'tags' => ['wildlife', 'sunset', 'safari'],
            'weather' => false,
        ],
    ];

    public function run(): void
    {
        $reviewer = ShowcaseAccountSeeder::reviewer();
        $publisher = app(ActivityPublicationService::class);
        $generator = app(ActivityScheduleGenerator::class);
        $categories = $this->categories();
        $published = 0;
        $sessions = 0;

        foreach (self::EXPERIENCES as $slug => $data) {
            $provider = ShowcaseProviderSeeder::provider($data['provider']);
            $destination = ShowcaseDestinationSeeder::destination($data['destination']);
            $category = $categories[$data['category']];

            $activity = Activity::withTrashed()->firstOrNew(['slug' => $slug]);
            if ($activity->trashed()) {
                $activity->restore();
            }

            $activity->fill([
                'provider_id' => $provider->id,
                'destination_id' => $destination->id,
                'category_id' => $category->id,
                'name' => $data['name'],
                'slug' => $slug,
                'type' => $category->name,
                'description' => $data['description'],
                'short_description' => $data['short_description'],
                'thumbnail' => ShowcaseDestinationSeeder::photo($data['thumbnail'], 1200),
                'gallery' => array_map(
                    fn (string $id) => ShowcaseDestinationSeeder::photo($id),
                    array_merge([$data['thumbnail']], $data['gallery']),
                ),
                'meta_title' => $data['name'].' · HolidayHub',
                'meta_description' => $data['short_description'],
                'tags' => $data['tags'],
                'highlights' => $data['highlights'],
                'inclusions' => $data['inclusions'],
                'base_price' => $data['price'],
                'currency' => 'KES',
                'duration_minutes' => $data['duration'],
                'capacity' => $data['capacity'],
                'included_participants' => 1,
                'min_age' => $data['min_age'],
                'max_age' => 99,
                'timezone' => $data['timezone'],
                'booking_mode' => 'shared',
                'minimum_notice_minutes' => 720,
                'booking_cutoff_minutes' => 720,
                'weather_dependent' => $data['weather'],
                'safety_instructions' => 'Follow your guide’s instructions at all times and tell your host about any medical conditions before you start.',
                'is_featured' => $data['featured'],
            ]);
            // Not listing fields, so they never demote a published activity.
            $activity->forceFill(['rating' => $data['rating'], 'reviews_count' => $data['reviews']]);

            if (! $activity->exists || $activity->isDirty()) {
                $activity->save();
            }

            $activity->locations()->updateOrCreate(
                ['type' => 'meeting_point', 'sequence' => 1],
                [
                    'name' => $data['place'],
                    'address' => $data['place'].', '.$destination->country,
                    'latitude' => (float) $destination->latitude,
                    'longitude' => (float) $destination->longitude,
                    'instructions' => 'Your host meets you here 15 minutes before the start time.',
                ],
            );

            $sessions += $this->schedule($activity, $data, $generator);
            $published += $this->publish($activity->refresh(), $publisher, $reviewer->id);
        }

        $this->command?->info(sprintf(
            'Showcase experiences ready: %d activities (%d newly published), %d sessions in the next 6 weeks.',
            count(self::EXPERIENCES), $published, $sessions,
        ));
    }

    /**
     * @return array<string, ActivityCategory>
     */
    private function categories(): array
    {
        $categories = [];
        foreach (self::CATEGORIES as $slug => $attributes) {
            $category = ActivityCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $attributes['name'], 'sort_order' => $attributes['sort_order'], 'is_active' => true],
            );
            if (! $category->is_active) {
                $category->update(['is_active' => true]);
            }
            $categories[$slug] = $category;
        }

        return $categories;
    }

    /**
     * Weekly slots, rolled forward so there are always ~6 weeks of sessions.
     */
    private function schedule(Activity $activity, array $data, ActivityScheduleGenerator $generator): int
    {
        $count = 0;
        $end = fn (string $start) => now()->setTimeFromTimeString($start)->addMinutes(min($data['duration'], 1439))->format('H:i');

        foreach ($data['slots'] as [$day, $start]) {
            $schedule = $activity->schedules()->firstOrCreate(
                ['day_of_week' => $day, 'start_time' => $start],
                [
                    'end_time' => $end($start),
                    'timezone' => $data['timezone'],
                    'capacity' => $data['capacity'],
                    'active_from' => now()->toDateString(),
                    'active_until' => now()->addMonths(3)->toDateString(),
                    'booking_cutoff_minutes' => 720,
                    'is_active' => true,
                ],
            );

            if ($schedule->active_until === null || $schedule->active_until->lt(now()->addWeeks(6))) {
                $schedule->update(['active_until' => now()->addMonths(3)->toDateString()]);
            }

            $count += $generator->generate($schedule->refresh(), now(), now()->addWeeks(6))->count();
        }

        return $count;
    }

    /**
     * @return int 1 when the activity was (re)published by this call
     */
    private function publish(Activity $activity, ActivityPublicationService $publisher, int $reviewerId): int
    {
        if ($activity->isPublished()) {
            return 0;
        }

        if (in_array($activity->status, [ActivityStatus::Suspended, ActivityStatus::Archived], true)) {
            $activity->forceFill([
                'status' => ActivityStatus::Draft,
                'verification_status' => ActivityVerificationStatus::Unverified,
            ])->save();
        }

        if (in_array($activity->status, [ActivityStatus::Draft, ActivityStatus::Rejected], true)) {
            $activity = $publisher->submit($activity);
        }
        if (in_array($activity->status, [ActivityStatus::Submitted, ActivityStatus::UnderReview], true)) {
            $activity = $publisher->approve($activity, $reviewerId);
        }
        if ($activity->status === ActivityStatus::Approved) {
            $activity = $publisher->publish($activity);
        }
        // A Published row that is merely inactive (or not yet approved) is
        // not reachable through the service; it is not expected here.

        return $activity->isPublished() ? 1 : 0;
    }
}
