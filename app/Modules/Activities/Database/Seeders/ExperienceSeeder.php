<?php

namespace App\Modules\Activities\Database\Seeders;

use App\Modules\Activities\Application\Services\ActivityScheduleGenerator;
use App\Modules\Activities\Domain\Enums\ActivityStatus;
use App\Modules\Activities\Domain\Enums\ActivityVerificationStatus;
use App\Modules\Activities\Domain\Models\Activity;
use App\Modules\Activities\Domain\Models\ActivityCategory;
use App\Modules\Destinations\Domain\Models\Destination;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * The sellable experiences catalogue.
 *
 * Activities are the product this platform sells, so they are seeded as real
 * experiences with a name worth buying, a price worth paying and a place worth
 * travelling to — not factory sentences. Each row is published and verified so
 * the public experiences section has inventory the moment it is seeded.
 *
 * Category slugs match `config/experiences.php` reel pools, and each
 * experience's thumbnail is the poster of the clip its category rotates first,
 * which keeps the photography and the footage saying the same thing.
 */
class ExperienceSeeder extends Seeder
{
    /**
     * Experience categories, in browse order.
     */
    private const CATEGORIES = [
        'safari-wildlife' => ['name' => 'Safari & Wildlife', 'sort_order' => 1],
        'ocean-water' => ['name' => 'Ocean & Water', 'sort_order' => 2],
        'adventure-trekking' => ['name' => 'Adventure & Trekking', 'sort_order' => 3],
        'culture-heritage' => ['name' => 'Culture & Heritage', 'sort_order' => 4],
        'food-markets' => ['name' => 'Food & Markets', 'sort_order' => 5],
        'wellness-retreats' => ['name' => 'Wellness & Retreats', 'sort_order' => 6],
        'family-kids' => ['name' => 'Family & Kids', 'sort_order' => 7],
    ];

    /**
     * The catalogue.
     *
     * `place` becomes the meeting point shown on the reel and the card,
     * `clips` is the reel pool key used to pick house footage.
     */
    private const EXPERIENCES = [
        [
            'category' => 'safari-wildlife',
            'name' => 'Sunrise Game Drive Through the Mara',
            'place' => 'Maasai Mara National Reserve',
            'short_description' => 'Leave the gate at first light for the cats, then breakfast on the escarpment while the herds cross below.',
            'description' => 'The Mara does its best work before nine. A small-window 4x4 takes you out at first light, over the river crossings and along the eastern escarpment, stopping wherever the day dictates — cheetah on the hunt, elephants crossing, a pride that has decided to sleep in the open. Your guide and spotter read the grass and the light, so you are never guessing when to be ready. Breakfast is served on the hill: eggs, tea and a thermos, with the plains spread out underneath you.',
            'duration_minutes' => 360,
            'base_price' => 18500,
            'capacity' => 6,
            'min_age' => 6,
            'highlights' => ['Small-window 4x4 with a spotter', 'Escarpment breakfast above the plains', 'Dawn chorus photo stop', 'Binoculars and blankets provided'],
            'inclusions' => ['Park fees', 'English-speaking guide', 'Breakfast', 'Hot drinks', 'Binoculars'],
            'tags' => ['wildlife', 'sunrise', 'photography', 'private'],
            'featured' => true,
        ],
        [
            'category' => 'safari-wildlife',
            'name' => 'Rhino Sanctuary Walk With a Ranger',
            'place' => 'Ol Pejeta Conservancy',
            'short_description' => 'On foot, at rhino pace, with the ranger who knows every horn and every footprint.',
            'description' => 'Two hours at a walking pace behind a black and white rhino, with a conservancy ranger explaining the tracking work that goes into protecting them. This is the quietest, most affecting way to meet the species: close enough to hear the breathing, far enough to keep the animal relaxed. Children welcome, and the tracking side of the day is genuinely gripping.',
            'duration_minutes' => 120,
            'base_price' => 9500,
            'capacity' => 8,
            'min_age' => 4,
            'highlights' => ['Two endangered rhino species', 'Conservancy ranger tracking talk', 'Walking pace, no engine noise'],
            'inclusions' => ['Conservancy fees', 'Ranger guide', 'Water and tea'],
            'tags' => ['wildlife', 'conservation', 'walking'],
        ],
        [
            'category' => 'safari-wildlife',
            'name' => 'Elephant Herds of Amboseli at Dusk',
            'place' => 'Amboseli National Park',
            'short_description' => 'Big-tusked families crossing the swamps as Kilimanjaro turns pink behind them.',
            'description' => 'The Amboseli herds are enormous and photogenic, and the light on Kilimanjaro in the last hour of the day is the reason every wildlife photographer on this continent keeps coming back. We time the afternoon to be in the swamp as the temperature drops and the elephants start to move again.',
            'duration_minutes' => 240,
            'base_price' => 14500,
            'capacity' => 6,
            'min_age' => 6,
            'highlights' => ['Kilimanjaro backdrop', 'Dusk feeding herds', 'Long lenses available'],
            'inclusions' => ['Park fees', 'Guide', 'Photography stop'],
            'tags' => ['wildlife', 'photography', 'dusk'],
        ],
        [
            'category' => 'ocean-water',
            'name' => 'Reef Snorkel With a Marine Biologist',
            'place' => 'Watamu Marine Park',
            'short_description' => 'Two hours in the water with someone who can name every fish on the reef.',
            'description' => 'A guided snorkel over a shallow reef where the visibility is good enough to see the whole wall. What makes it different is the guide: a marine biologist who talks you through the resident octopus, the parrotfish grazing and the way a healthy reef sounds underwater. Gear, wetsuit and a floating guide line are all included.',
            'duration_minutes' => 150,
            'base_price' => 7200,
            'capacity' => 10,
            'min_age' => 8,
            'highlights' => ['Floating guide line', 'Marine biologist guide', 'Full gear and wetsuit'],
            'inclusions' => ['Marine park fees', 'Snorkel gear', 'Wetsuit', 'Soft drinks'],
            'tags' => ['snorkelling', 'marine', 'guided'],
            'featured' => true,
        ],
        [
            'category' => 'ocean-water',
            'name' => 'Dhow Sail Into the Sunset',
            'place' => 'Lamu Island',
            'short_description' => 'A lateen-rigged dhow, a short tacking fight, and the Swahili coast going gold.',
            'description' => 'Sail out of the lagoon on a traditional dhow with a captain who has crossed these waters his whole life. You can help crew the tacking, or sit on the windward side and watch the coast unspool. Swahili snacks on board, and back in town in time for a rooftop dinner.',
            'duration_minutes' => 180,
            'base_price' => 8800,
            'capacity' => 12,
            'min_age' => 5,
            'highlights' => ['Traditional lateen dhow', 'Swahili snacks aboard', 'Back in town by nightfall'],
            'inclusions' => ['Sailboat charter', 'Crew', 'Swahili snacks', 'Marine fees'],
            'tags' => ['sailing', 'sunset', 'swahili'],
        ],
        [
            'category' => 'ocean-water',
            'name' => 'Beginner Surf Session on the Sandbar',
            'place' => 'Diani Beach',
            'short_description' => 'Warm water, forgiving waves, and a coach who has never once raised its voice.',
            'description' => 'A two-hour introduction on a wide, sandy break — the right place to learn. The coach runs you through pop-up technique, then into the water for a first wave, then as many more as you want. Boards, rash vests and a water taxi to the break are included.',
            'duration_minutes' => 120,
            'base_price' => 6500,
            'capacity' => 8,
            'min_age' => 10,
            'highlights' => ['Sandy entry beach', 'One coach per two surfers', 'Water taxi to the break'],
            'inclusions' => ['Board hire', 'Rash vest', 'Coach', 'Water taxi'],
            'tags' => ['surfing', 'beginner', 'beach'],
        ],
        [
            'category' => 'adventure-trekking',
            'name' => 'Climb Hell’s Gate and Walk the Rim',
            'place' => 'Hell’s Gate National Park',
            'short_description' => 'Fishermen’s stairs, a lava cliff walk with the whole valley below you, and a hot spring at the end.',
            'description' => 'Up through the Fishermen’s Trail and out along the rim of an old collapsed volcano, with views down the escarpment to the plains. The walk is about three hours including the climb, and it ends at the park’s hot springs where a swim is the correct decision. One of the best walks in the country, and the reason most people here have never heard of it.',
            'duration_minutes' => 300,
            'base_price' => 6800,
            'capacity' => 10,
            'min_age' => 10,
            'highlights' => ['Fishermen’s Trail ascent', 'Rim walk above the valley', 'Geothermal hot spring finish'],
            'inclusions' => ['Park fees', 'Guide', 'Water and packed lunch'],
            'tags' => ['hiking', 'geology', 'views'],
            'featured' => true,
        ],
        [
            'category' => 'adventure-trekking',
            'name' => 'Rift Valley Sunrise Hike',
            'place' => 'Lake Naivasha',
            'short_description' => 'Climb before breakfast, then eat it looking down a valley full of lakes.',
            'description' => 'An early start on a ridge trail above Lake Naivasha, timed so you reach the top for sunrise. The descent ends with a full breakfast — eggs, pancakes, tea — at a viewpoint over the valley floor. Back at the lodge by mid-morning, with the rest of the day free.',
            'duration_minutes' => 270,
            'base_price' => 5900,
            'capacity' => 10,
            'min_age' => 12,
            'highlights' => ['Sunrise from the ridge', 'Breakfast at the viewpoint', 'Escarpment views'],
            'inclusions' => ['Guide', 'Breakfast', 'Water'],
            'tags' => ['hiking', 'sunrise', 'valley'],
        ],
        [
            'category' => 'adventure-trekking',
            'name' => 'Cycling the Old Coastal Road',
            'place' => 'Likoni to Kaya Kinono',
            'short_description' => 'A half-day ride along the old road where the ocean is on one side and the cliff on the other.',
            'description' => 'A gentle, mostly flat ride out of the old road, stopping at a dhow yard, a coral temple and a roadside fish grill. Roughly eighteen kilometres over five hours with plenty of time off the saddle. Bikes, helmets, a support vehicle and the grilled fish lunch are all in the price.',
            'duration_minutes' => 330,
            'base_price' => 9200,
            'capacity' => 10,
            'min_age' => 12,
            'highlights' => ['Dhow yard visit', 'Coral temple', 'Fish grill lunch'],
            'inclusions' => ['Bike and helmet', 'Support vehicle', 'Lunch', 'Guide'],
            'tags' => ['cycling', 'coast', 'food'],
        ],
        [
            'category' => 'culture-heritage',
            'name' => 'Old Town Walking Tour and Swahili Lunch',
            'place' => 'Lamu Old Town',
            'short_description' => 'Coral stone, carved doors, and lunch in a house that has been standing four hundred years.',
            'description' => 'A slow walk through the old town with a Swahili guide who grew up in it: the carved doors and their symbolism, the history of the Indian Ocean trade, the mosques and the shrines, and why every roof is built the way it is. It finishes with lunch in a family home — biryani, samosas and a great deal of tea.',
            'duration_minutes' => 210,
            'base_price' => 7500,
            'capacity' => 10,
            'min_age' => 8,
            'highlights' => ['Swahili historian guide', 'Carved doors and roof symbolism', 'Lunch in a heritage house'],
            'inclusions' => ['Guide', 'Lunch', 'Tea and coffee', 'Museum entries'],
            'tags' => ['culture', 'history', 'food'],
            'featured' => true,
        ],
        [
            'category' => 'culture-heritage',
            'name' => 'Maasai Village Visit With a Dance Evening',
            'place' => 'Loita Hills',
            'short_description' => 'A home visit in the Loita hills, and a night of song and dance that nobody wants to leave.',
            'description' => 'Spend the afternoon in a Maasai community in the Loita hills: a walk with the elders, the honey harvest, and questions answered with real candour about the land and the drought. The evening is the part people remember — a dance around the fire that goes on much longer than planned, with dinner served alongside.',
            'duration_minutes' => 420,
            'base_price' => 12000,
            'capacity' => 8,
            'min_age' => 10,
            'highlights' => ['Elder-led walk and history', 'Honey harvest', 'Fire-circle dance evening'],
            'inclusions' => ['Community fee', 'Dinner', 'Guide and translator'],
            'tags' => ['culture', 'community', 'music'],
        ],
        [
            'category' => 'culture-heritage',
            'name' => 'Crafts and Coffee With a Master Potter',
            'place' => 'Nyeri Highlands',
            'short_description' => 'Throw a pot, break a hundred coffee legends, taste what you just roasted.',
            'description' => 'A morning with a fourth-generation potter: the clay, the wheel, the kiln, and the fire that has to be right. In the afternoon you cup coffee the long way, tasting the difference between washed and natural lots and never again treating a Kenyan bean as anonymous.',
            'duration_minutes' => 300,
            'base_price' => 11000,
            'capacity' => 8,
            'min_age' => 10,
            'highlights' => ['Hands-on potter’s wheel', 'Kiln and firing explained', 'Washed vs natural cupping'],
            'inclusions' => ['Materials and firing', 'Coffee cupping', 'Lunch', 'Take-home piece'],
            'tags' => ['craft', 'coffee', 'hands-on'],
        ],
        [
            'category' => 'food-markets',
            'name' => 'Market Walk and Nyama Choma Lunch',
            'place' => 'Nairobi City Market',
            'short_description' => 'Sisal, spice and smoke — then the grill the whole market has been queuing for.',
            'description' => 'A guided walk through the market stalls with a local food writer: what to buy, what to eat standing up, and how to tell a good mango from a beautiful one. It ends at a long table with the city’s best nyama choma, and enough time to put away more than you planned.',
            'duration_minutes' => 180,
            'base_price' => 6400,
            'capacity' => 12,
            'min_age' => 6,
            'highlights' => ['Local food writer guide', 'Spice and produce stalls', 'Lunch at a long table'],
            'inclusions' => ['Guide', 'Grill lunch', 'Fruit juice', 'Bottled water'],
            'tags' => ['food', 'market', 'city'],
            'featured' => true,
        ],
        [
            'category' => 'food-markets',
            'name' => 'Coastal Fish Grill by the Sand',
            'place' => 'Watamu',
            'short_description' => 'Line-caught fish, grilled on the spot, eaten twenty metres from the water.',
            'description' => 'The morning’s catch comes in at nine. You pick your fish from the ice, it goes on the grill, and you eat it on the sand with kachumber and coconut rice while the boats go back out. Utterly simple, and hard to beat.',
            'duration_minutes' => 150,
            'base_price' => 5800,
            'capacity' => 16,
            'min_age' => 4,
            'highlights' => ['Fish chosen from the morning catch', 'Grilled on the sand', 'Beachfront seating'],
            'inclusions' => ['Fish and sides', 'Soft drinks', 'Beach access'],
            'tags' => ['food', 'seafood', 'beach'],
        ],
        [
            'category' => 'food-markets',
            'name' => 'Tea Estate Walk and Factory Cupping',
            'place' => 'Limuru',
            'short_description' => 'Walk the plucking rows at dawn, then cup the leaf that came off them today.',
            'description' => 'Out into the fields at first light while the pickers are working, then through the factory to see the leaf come in, wither, roll and dry. You end at the cupping table tasting the grades side by side and learning why the one from the top slope is worth what it costs.',
            'duration_minutes' => 240,
            'base_price' => 7900,
            'capacity' => 12,
            'min_age' => 8,
            'highlights' => ['Dawn walk with the pickers', 'Factory tour', 'Guided cupping'],
            'inclusions' => ['Estate fees', 'Factory tour', 'Cupping', 'Breakfast'],
            'tags' => ['tea', 'food', 'walking'],
        ],
        [
            'category' => 'wellness-retreats',
            'name' => 'Beachside Sunrise Yoga and Breakfast',
            'place' => 'Diani Beach',
            'short_description' => 'An hour on the sand, a long breakfast, and no phone in sight.',
            'description' => 'A gentle, well-sequenced session on a private stretch of sand as the sun comes up, finished with a cold-press juice and a full breakfast at a beachfront table. Ideal the morning after a long flight, and quietly the most restorative hour of a Kenyan holiday.',
            'duration_minutes' => 150,
            'base_price' => 6200,
            'capacity' => 12,
            'min_age' => 14,
            'highlights' => ['Private beachfront space', 'Qualified instructor', 'Breakfast after'],
            'inclusions' => ['Yoga session', 'Mat and towel', 'Breakfast', 'Juice'],
            'tags' => ['yoga', 'wellness', 'beach'],
        ],
        [
            'category' => 'wellness-retreats',
            'name' => 'Hammam and Salt Scrub',
            'place' => 'Zanzibar Stone Town',
            'short_description' => 'A stone hammam, a volcanic salt scrub, and a long tea afterwards.',
            'description' => 'Two and a half hours in a traditional Zanzibar hammam: steam, black soap, a vigorous scrub with volcanic salt, and a rosewater rinse. It is not gentle, and it is the best thing you will do for your skin while you are here. Finish with mint tea in the courtyard.',
            'duration_minutes' => 180,
            'base_price' => 11500,
            'capacity' => 6,
            'min_age' => 16,
            'highlights' => ['Traditional stone hammam', 'Volcanic salt scrub', 'Courtyard tea service'],
            'inclusions' => ['Hammam', 'Soap and scrub', 'Mint tea'],
            'tags' => ['spa', 'wellness', 'culture'],
        ],
        [
            'category' => 'wellness-retreats',
            'name' => 'Forest Bathing Walk Above the Canopy',
            'place' => 'Aberdare Range',
            'short_description' => 'Slow, deliberate walking in old forest, with nothing scheduled at all.',
            'description' => 'Shinrin-yoku, done properly: three unhurried hours in ancient forest above the canopy, with a guide trained in the practice and a lot of silence built in. No targets, no pace, no phones. It is the experience people book a second time.',
            'duration_minutes' => 180,
            'base_price' => 10400,
            'capacity' => 8,
            'min_age' => 16,
            'highlights' => ['Three hours of deliberate silence', 'Trained forest-bathing guide', 'Canopy viewpoint'],
            'inclusions' => ['Guide', 'Tea at the trailhead', 'Packed lunch'],
            'tags' => ['wellness', 'forest', 'slow'],
        ],
        [
            'category' => 'family-kids',
            'name' => 'Giraffe Feeding and Keeper Tour',
            'place' => 'Nairobi',
            'short_description' => 'Feed a giraffe at eye level, then find out why they do not sleep much.',
            'description' => 'Built for families with children. Meet the keepers, feed the giraffes from a raised platform at eye level, then walk the reserve with a keeper who can answer every question a child asks. Overwhelmingly good with under-tens.',
            'duration_minutes' => 150,
            'base_price' => 9600,
            'capacity' => 12,
            'min_age' => 3,
            'highlights' => ['Giraffe feeding platform', 'Keeper-led reserve walk', 'Designed for young children'],
            'inclusions' => ['Entry', 'Feed', 'Guide', 'Soft drinks'],
            'tags' => ['family', 'animals', 'kids'],
            'featured' => true,
        ],
        [
            'category' => 'family-kids',
            'name' => 'Gems and Scorpions at the Wildlands',
            'place' => 'Nairobi',
            'short_description' => 'A venomous snake handled calmly by an expert, and a great deal of awe from children.',
            'description' => 'A conservation programme that keeps its animals properly — no performing with wildlife. Children get close to the rhinos, hold a python with two handlers, and watch a keeper work with the scorpions. Serious about rescue and rehabilitation, and very good with kids.',
            'duration_minutes' => 180,
            'base_price' => 8400,
            'capacity' => 12,
            'min_age' => 5,
            'highlights' => ['Rescue and rehabilitation programme', 'Two-handler snake handling', 'Rhino encounter'],
            'inclusions' => ['Entry', 'Guide', 'Water'],
            'tags' => ['family', 'conservation', 'kids'],
        ],
        [
            'category' => 'family-kids',
            'name' => 'Stargazing and Storytelling on the Plains',
            'place' => 'Maasai Mara',
            'short_description' => 'No light pollution, a telescope, and the stories that go with the constellations.',
            'description' => 'After dinner, out into the dark with a telescope and a Maasai guide who names the constellations the way her grandmother did. Kids who have been told the Southern Cross is a pair of eyes are usually the ones who remember it longest.',
            'duration_minutes' => 120,
            'base_price' => 5400,
            'capacity' => 16,
            'min_age' => 5,
            'highlights' => ['Telescope and star chart', 'Maasai constellations', 'Hot cocoa and popcorn'],
            'inclusions' => ['Guide', 'Telescope', 'Hot drinks'],
            'tags' => ['family', 'stars', 'evening'],
        ],
    ];

    public function run(): void
    {
        $destinations = Destination::query()->orderBy('id')->get();
        $providers = Provider::query()->orderBy('id')->get();

        if ($destinations->isEmpty() || $providers->isEmpty()) {
            $this->command?->warn('Experiences need destinations and providers; seed those first.');

            return;
        }

        $categories = $this->seedCategories();
        $pool = (array) config('experiences.reels', []);
        $created = 0;

        foreach (array_values(self::EXPERIENCES) as $index => $experience) {
            $slug = Str::slug($experience['name']);

            if (Activity::query()->withoutGlobalScopes()->where('slug', $slug)->exists()) {
                continue;
            }

            $category = $categories[$experience['category']];
            $destination = $destinations[$index % $destinations->count()];
            $provider = $providers[$index % $providers->count()];

            $poster = $this->posterFor($pool[$experience['category']] ?? []);

            $activity = Activity::query()->create([
                'provider_id' => $provider->id,
                'destination_id' => $destination->id,
                'category_id' => $category->id,
                'name' => $experience['name'],
                'slug' => $slug,
                'type' => $category->name,
                'description' => $experience['description'],
                'short_description' => $experience['short_description'],
                'thumbnail' => $poster,
                'meta_title' => $experience['name'].' · HolidayHub',
                'meta_description' => $experience['short_description'],
                'tags' => $experience['tags'],
                'base_price' => $experience['base_price'],
                'currency' => 'KES',
                'duration_minutes' => $experience['duration_minutes'],
                'capacity' => $experience['capacity'],
                'included_participants' => 1,
                'min_age' => $experience['min_age'],
                'max_age' => 99,
                'timezone' => 'Africa/Nairobi',
                'booking_mode' => 'shared',
                'highlights' => $experience['highlights'],
                'inclusions' => $experience['inclusions'],
                'minimum_notice_minutes' => 720,
                'booking_cutoff_minutes' => 720,
                'weather_dependent' => str_contains(strtolower($experience['category']), 'ocean')
                    || str_contains(strtolower($experience['category']), 'adventure'),
                'safety_instructions' => 'Follow your guide at all times and stay within the marked area.',
                'is_featured' => $experience['featured'] ?? false,
                'rating' => 4.6,
            ]);

            // The model forces `is_active` false on create and demotes a
            // published activity back to draft when its listing fields change,
            // so the publish state is applied after creation, not during it.
            $activity->forceFill([
                'status' => ActivityStatus::Published,
                'verification_status' => ActivityVerificationStatus::Approved,
                'is_active' => true,
                'published_at' => now(),
                'verified_at' => now(),
            ])->save();

            $activity->locations()->create([
                'type' => 'meeting_point',
                'name' => $experience['place'],
                'address' => $experience['place'].', '.$destination->country,
                'latitude' => (float) $destination->latitude,
                'longitude' => (float) $destination->longitude,
                'instructions' => 'Your host meets you here. Bring water and sun protection.',
                'sequence' => 1,
            ]);

            $this->seedSchedule($activity);
            $created++;
        }

        $this->command?->info(sprintf('Published %d experiences across %d categories.', $created, count($categories)));
    }

    /**
     * @return array<string, ActivityCategory>
     */
    private function seedCategories(): array
    {
        $categories = [];

        foreach (self::CATEGORIES as $slug => $attributes) {
            $categories[$slug] = ActivityCategory::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $attributes['name'], 'sort_order' => $attributes['sort_order'], 'is_active' => true],
            );
        }

        return $categories;
    }

    /**
     * The photograph an experience is listed with: the poster of the first clip
     * its category rotates, so the grid and the feed always agree.
     */
    private function posterFor(array $pool): ?string
    {
        $first = $pool[0] ?? null;

        return $first ? config('experiences.clips.'.$first.'.poster') : null;
    }

    /**
     * Weekly sessions plus generated instances, so the booking flow has
     * bookable dates the moment an experience is published.
     */
    private function seedSchedule(Activity $activity): void
    {
        if ($activity->schedules()->exists()) {
            return;
        }

        $schedule = $activity->schedules()->create([
            'day_of_week' => now()->addDay()->dayOfWeek,
            'start_time' => '09:00',
            'end_time' => '12:00',
            'timezone' => $activity->timezone,
            'capacity' => $activity->capacity,
            'active_from' => now()->toDateString(),
            'active_until' => now()->addMonths(3)->toDateString(),
            'booking_cutoff_minutes' => $activity->booking_cutoff_minutes,
            'is_active' => true,
        ]);

        app(ActivityScheduleGenerator::class)->generate($schedule, now(), now()->addDays(30));
    }
}