<?php

namespace App\Modules\Destinations\Database\Seeders;

use App\Modules\Destinations\Domain\Models\Destination;
use Illuminate\Database\Seeder;

/**
 * Showcase destinations with real photography.
 *
 * Every image is an Unsplash photo whose URL was checked to return HTTP 200 and
 * whose content was looked at, so what the copy says matches what the photo
 * shows. Existing destinations are never touched; showcase rows are matched on
 * slug and updated in place, so re-running the seeder is safe.
 */
class ShowcaseDestinationSeeder extends Seeder
{
    public const DESTINATIONS = [
        'diani-beach' => [
            'name' => 'Diani Beach',
            'city' => 'Diani',
            'country' => 'Kenya',
            'tagline' => 'Powder-white sand on the Indian Ocean',
            'short_description' => 'Seventeen kilometres of white sand south of Mombasa, fringed by palms and a reef-calm lagoon.',
            'description' => 'Diani is the Kenyan coast at its easiest: a long, flat, powder-white beach protected by an offshore reef, so the water stays shallow and turquoise at low tide. Days run on kitesurfing, snorkelling trips to Kisite, Swahili seafood lunches and afternoons by the pool, with Colobus monkeys in the coastal forest behind the beach.',
            'latitude' => -4.3167,
            'longitude' => 39.5833,
            'image' => '1667935837291-1dc178866251',
            'gallery' => ['1708119063168-4785d1359824', '1582719508461-905c673771fd'],
            'is_featured' => true,
            'popularity_score' => 96,
            'best_season' => 'December – March',
            'average_cost' => 18000,
            'highlights' => ['Reef-calm lagoon swimming', 'Kitesurfing season Dec–Mar', 'Colobus monkeys in Jadini Forest', 'Day trips to Kisite marine park'],
            'tags' => ['beach', 'coast', 'family', 'watersports'],
        ],
        'watamu' => [
            'name' => 'Watamu',
            'city' => 'Watamu',
            'country' => 'Kenya',
            'tagline' => 'Marine-park reefs and turtle beaches',
            'short_description' => 'A quiet fishing village inside a protected marine park, with coral gardens a short swim from shore.',
            'description' => 'Watamu sits inside one of the oldest marine parks in Africa. Coral gardens start a few hundred metres off the beach, green turtles nest on the sand, and Mida Creek’s mangroves fill with birdlife at low tide. It is slower and smaller than Diani, with beach lodges rather than big resorts.',
            'latitude' => -3.3540,
            'longitude' => 40.0240,
            'image' => '1651860282131-e3257674ccd1',
            'gallery' => ['1544551763-46a013bb70d5', '1566073771259-6a8506099945'],
            'is_featured' => true,
            'popularity_score' => 88,
            'best_season' => 'October – March',
            'average_cost' => 15000,
            'highlights' => ['Watamu Marine National Park', 'Mida Creek mangroves', 'Turtle nesting beaches', 'Gede ruins'],
            'tags' => ['beach', 'snorkelling', 'diving', 'quiet'],
        ],
        'lamu-island' => [
            'name' => 'Lamu Island',
            'city' => 'Lamu',
            'country' => 'Kenya',
            'tagline' => 'Dhows, donkeys and a 700-year-old Swahili town',
            'short_description' => 'A car-free island where dhows still work the channel and the old town is a UNESCO World Heritage site.',
            'description' => 'There are no cars on Lamu. You arrive by boat, walk the narrow lanes of the old town, and spend the evenings on the water as the dhows come back under sail. Shela’s dunes and empty beach are a short boat ride away, and the carved Swahili doors and rooftop terraces make it one of the most atmospheric places on the coast.',
            'latitude' => -2.2717,
            'longitude' => 40.9020,
            'image' => '1664093671658-a2aac3a344a9',
            'gallery' => ['1602002418082-a4443e081dd1', '1611892440504-42a792e24d32'],
            'is_featured' => true,
            'popularity_score' => 84,
            'best_season' => 'November – April',
            'average_cost' => 14000,
            'highlights' => ['UNESCO-listed old town', 'Sunset dhow sails', 'Shela beach and dunes', 'Car-free island life'],
            'tags' => ['culture', 'island', 'heritage', 'slow travel'],
        ],
        'maasai-mara' => [
            'name' => 'Maasai Mara',
            'city' => 'Narok',
            'country' => 'Kenya',
            'tagline' => 'Big cats and the Great Migration',
            'short_description' => 'Kenya’s most famous reserve: open grassland, river crossings and the densest big-cat population in Africa.',
            'description' => 'The Mara is classic East African savanna: rolling grassland dotted with acacia, the Mara and Talek rivers, and resident lions, leopards and cheetahs year-round. From July to October the wildebeest migration pours across from the Serengeti. Dawn game drives, hot-air balloon flights and sundowners on the escarpment are the rhythm of the day.',
            'latitude' => -1.4900,
            'longitude' => 35.1440,
            'image' => '1516426122078-c23e76319801',
            'gallery' => ['1507608616759-54f48f0af0ee', '1585402969048-86f38f8bfc2e'],
            'is_featured' => true,
            'popularity_score' => 99,
            'best_season' => 'July – October',
            'average_cost' => 45000,
            'highlights' => ['Great Migration river crossings', 'Hot-air balloon safaris', 'Resident big cats', 'Maasai community visits'],
            'tags' => ['safari', 'wildlife', 'migration', 'photography'],
        ],
        'amboseli' => [
            'name' => 'Amboseli',
            'city' => 'Kajiado',
            'country' => 'Kenya',
            'tagline' => 'Elephants beneath Kilimanjaro',
            'short_description' => 'Big-tusked elephant herds on open plains, with Kilimanjaro’s snow cap filling the horizon.',
            'description' => 'Amboseli is where the classic photograph comes from: elephant families crossing the plains with the snow-capped dome of Kilimanjaro behind them. Swamps fed by the mountain’s meltwater keep the herds here year-round, and the clear mornings are the best time to see the peak before the clouds close in.',
            'latitude' => -2.6527,
            'longitude' => 37.2606,
            'image' => '1489392191049-fc10c97e64b6',
            'gallery' => ['1613061445510-e296bfedb73e', '1557178985-891ca9b9b01c'],
            'is_featured' => true,
            'popularity_score' => 91,
            'best_season' => 'June – October',
            'average_cost' => 32000,
            'highlights' => ['Views of Kilimanjaro', 'Large elephant herds', 'Observation Hill', 'Swamp birdlife'],
            'tags' => ['safari', 'elephants', 'kilimanjaro', 'photography'],
        ],
        'tsavo' => [
            'name' => 'Tsavo',
            'city' => 'Voi',
            'country' => 'Kenya',
            'tagline' => 'Wild, empty bush and red-dust elephants',
            'short_description' => 'Kenya’s largest wilderness, halfway between Nairobi and the coast, with long drives and very few vehicles.',
            'description' => 'Tsavo East and West together are bigger than many countries, and it shows: you can drive for an hour without seeing another vehicle. Elephants dusted red by the soil, giraffes silhouetted against acacia at sunset and the lava flows of Shetani make it a rewarding stop between Nairobi and the coast.',
            'latitude' => -2.9833,
            'longitude' => 38.4667,
            'image' => '1535941339077-2dd1c7963098',
            'gallery' => ['1523805009345-7448845a9e53', '1547471080-7cc2caa01a7e'],
            'is_featured' => false,
            'popularity_score' => 72,
            'best_season' => 'June – October',
            'average_cost' => 26000,
            'highlights' => ['Red elephants', 'Uncrowded game drives', 'Mzima Springs', 'Acacia sunsets'],
            'tags' => ['safari', 'wilderness', 'road trip'],
        ],
        'mount-kenya' => [
            'name' => 'Mount Kenya',
            'city' => 'Nanyuki',
            'country' => 'Kenya',
            'tagline' => 'Africa’s second summit, from the farm gate',
            'short_description' => 'Forest, moorland and glaciated peaks above Nanyuki, with highland farm stays at the trailhead.',
            'description' => 'Mount Kenya rises through bamboo forest and giant-groundsel moorland to jagged, glaciated peaks. Day hikers head for the moorlands above the Sirimon gate; trekkers take four or five days to Point Lenana. Down in Nanyuki the air is cool, the farm stays are cosy, and Ol Pejeta’s rhinos are a short drive away.',
            'latitude' => -0.0167,
            'longitude' => 37.0739,
            'image' => '1707819487421-21228af983a9',
            'gallery' => ['1551632811-561732d1e306'],
            'is_featured' => false,
            'popularity_score' => 68,
            'best_season' => 'January – February, July – September',
            'average_cost' => 12000,
            'highlights' => ['Moorland day hikes', 'Point Lenana trek', 'Equator crossing at Nanyuki', 'Nearby Ol Pejeta Conservancy'],
            'tags' => ['hiking', 'mountains', 'adventure', 'cool climate'],
        ],
        'kyoto' => [
            'name' => 'Kyoto',
            'city' => 'Kyoto',
            'country' => 'Japan',
            'tagline' => 'Temples, tea houses and lantern-lit lanes',
            'short_description' => 'Japan’s old imperial capital: wooden machiya streets, pagodas and a thousand-year tea culture.',
            'description' => 'Kyoto rewards slow walking. The preserved lanes of Higashiyama climb past wooden tea houses to the Yasaka pagoda, Gion’s streets glow at dusk, and the city is ringed by temple-covered hills that turn red and gold in autumn. Come for the tea ceremony, stay for the food.',
            'latitude' => 35.0116,
            'longitude' => 135.7681,
            'image' => '1545569341-9eb8b30979d9',
            'gallery' => ['1493976040374-85c8e12f0c0e', '1515823064-d6e0c04616a7'],
            'is_featured' => true,
            'popularity_score' => 93,
            'best_season' => 'March – May, October – November',
            'average_cost' => 30000,
            'highlights' => ['Higashiyama and the Yasaka pagoda', 'Gion at dusk', 'Tea ceremony', 'Autumn colours'],
            'tags' => ['culture', 'city', 'food', 'temples'],
        ],
        'cinque-terre' => [
            'name' => 'Vernazza, Cinque Terre',
            'city' => 'Vernazza',
            'country' => 'Italy',
            'tagline' => 'Pastel harbours on the Ligurian cliffs',
            'short_description' => 'A tiny harbour village of painted houses, linked to its neighbours by cliff paths and the coastal train.',
            'description' => 'Vernazza is the postcard of the Cinque Terre: a cluster of pastel houses around a little harbour, a castle tower on the rock and terraced vineyards climbing the cliffs behind. Walk the coastal path to Monterosso, swim off the rocks, and eat trofie al pesto with a glass of local white as the sun goes down.',
            'latitude' => 44.1350,
            'longitude' => 9.6847,
            'image' => '1534445867742-43195f401b6c',
            'gallery' => [],
            'is_featured' => false,
            'popularity_score' => 86,
            'best_season' => 'May – June, September',
            'average_cost' => 28000,
            'highlights' => ['Sentiero Azzurro cliff path', 'Harbour swimming', 'Terraced vineyards', 'Ligurian pesto'],
            'tags' => ['coast', 'europe', 'hiking', 'food'],
        ],
        'marrakech-sahara' => [
            'name' => 'Marrakech & the Sahara',
            'city' => 'Marrakech',
            'country' => 'Morocco',
            'tagline' => 'Souks, riads and nights under desert stars',
            'short_description' => 'The medina and souks of Marrakech, then over the Atlas to the dunes of Erg Chebbi.',
            'description' => 'Start in Marrakech, where the Koutoubia minaret rises over the medina and the snow-capped Atlas mountains sit on the horizon. Then cross the mountains to the Sahara: orange dunes at Erg Chebbi, camel treks at sunset and a night in a desert camp with more stars than you have ever seen.',
            'latitude' => 31.6295,
            'longitude' => -7.9811,
            'image' => '1597212618440-806262de4f6b',
            'gallery' => ['1559586616-361e18714958', '1613169620329-6785c004d900'],
            'is_featured' => false,
            'popularity_score' => 81,
            'best_season' => 'March – May, October – November',
            'average_cost' => 22000,
            'highlights' => ['Jemaa el-Fnaa and the souks', 'Atlas mountain crossing', 'Erg Chebbi dunes', 'Desert camp nights'],
            'tags' => ['culture', 'desert', 'adventure', 'markets'],
        ],
    ];

    public static function photo(string $id, int $width = 1400): string
    {
        return "https://images.unsplash.com/photo-{$id}?auto=format&fit=crop&w={$width}&q=84";
    }

    public function run(): void
    {
        $touched = 0;

        foreach (self::DESTINATIONS as $slug => $data) {
            $destination = Destination::withTrashed()->firstOrNew(['slug' => $slug]);

            $destination->forceFill([
                'name' => $data['name'],
                'city' => $data['city'],
                'country' => $data['country'],
                'tagline' => $data['tagline'],
                'short_description' => $data['short_description'],
                'description' => $data['description'],
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
                'image_url' => self::photo($data['image']),
                'thumbnail' => self::photo($data['image'], 640),
                'gallery' => array_map(fn (string $id) => self::photo($id), array_merge([$data['image']], $data['gallery'])),
                'is_featured' => $data['is_featured'],
                'popularity_score' => $data['popularity_score'],
                'best_season' => $data['best_season'],
                'average_cost' => $data['average_cost'],
                'highlights' => $data['highlights'],
                'tags' => $data['tags'],
                'meta_title' => $data['name'].' holidays · HolidayHub',
                'meta_description' => $data['short_description'],
                'deleted_at' => null,
            ]);

            // latitude/longitude have no cast, so the stored numeric string
            // ("-4.3167000") would always look dirty against the float.
            foreach (['latitude', 'longitude'] as $coordinate) {
                if ($destination->exists && (float) $destination->getOriginal($coordinate) === (float) $data[$coordinate]) {
                    $destination->setAttribute($coordinate, $destination->getOriginal($coordinate));
                }
            }

            if (! $destination->exists || $destination->isDirty()) {
                $destination->save();
                $touched++;
            }
        }

        $this->command?->info(sprintf('Showcase destinations ready (%d created or updated).', $touched));
    }

    public static function destination(string $slug): Destination
    {
        return Destination::query()->where('slug', $slug)->firstOrFail();
    }
}
