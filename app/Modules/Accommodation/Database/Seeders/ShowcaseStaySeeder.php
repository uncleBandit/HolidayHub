<?php

namespace App\Modules\Accommodation\Database\Seeders;

use App\Modules\Accommodation\Application\Services\AccommodationPublicationService;
use App\Modules\Accommodation\Domain\Enums\AccommodationStatus;
use App\Modules\Accommodation\Domain\Enums\VerificationStatus;
use App\Modules\Accommodation\Domain\Models\Accommodation;
use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use App\Modules\Catalog\Database\Seeders\AmenitySeeder;
use App\Modules\Catalog\Domain\Models\Amenity;
use App\Modules\Destinations\Database\Seeders\ShowcaseDestinationSeeder;
use App\Modules\Identity\Database\Seeders\ShowcaseAccountSeeder;
use App\Modules\Providers\Database\Seeders\ShowcaseProviderSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

/**
 * Published, approved showcase stays: hotels, villas and B&Bs.
 *
 * Properties are written through their own models so the
 * SyncsCanonicalAccommodation hook builds the canonical `accommodations` row,
 * then go through AccommodationPublicationService (submit -> approve) exactly
 * like a provider listing reviewed by an admin. Prices are KES per night.
 *
 * Idempotent: properties match on slug, room types on slug, rooms on name.
 * A property is only saved when something actually changed, so an unchanged
 * re-run does not trip the "listing changed, back to draft" rule; if a listing
 * has dropped out of Published for any reason it is re-submitted and approved.
 */
class ShowcaseStaySeeder extends Seeder
{
    private const POLICIES = [
        'check_in' => '14:00',
        'check_out' => '10:00',
        'cancellation' => 'Free cancellation up to 7 days before arrival; 50% refund up to 48 hours before.',
        'children' => 'Children of all ages are welcome.',
        'pets' => 'No pets, except assistance animals.',
        'smoking' => 'Non-smoking indoors.',
    ];

    public const HOTELS = [
        'coral-tide-beach-hotel-diani' => [
            'provider' => 'coast',
            'destination' => 'diani-beach',
            'name' => 'Coral Tide Beach Hotel',
            'address' => 'Diani Beach Road',
            'description' => 'A white, low-rise beach hotel set in palm gardens directly behind Diani’s reef-calm lagoon. The long pool runs between the palms down towards the sand, rooms are bright and airy with ceiling fans and air conditioning, and breakfast is served on the terrace. Snorkel trips to Kisite and kitesurf lessons can be arranged at reception.',
            'cover' => '1564501049412-61c2a3083791',
            'gallery' => ['1667935837291-1dc178866251', '1631049307264-da0ec9d70304'],
            'stars' => 4,
            'rating' => 4.6,
            'reviews' => 214,
            'featured' => true,
            'amenities' => ['free-wi-fi', 'swimming-pool', 'air-conditioning', 'room-service', '247-reception', 'parking'],
            'room_types' => [
                ['name' => 'Garden Double', 'price' => 14500, 'capacity' => 2, 'beds' => 1, 'rooms' => 6, 'description' => 'Ground-floor double opening onto the palm garden.'],
                ['name' => 'Ocean King', 'price' => 21000, 'capacity' => 2, 'beds' => 1, 'rooms' => 4, 'description' => 'King bed and a balcony facing the lagoon.'],
                ['name' => 'Family Suite', 'price' => 32000, 'capacity' => 4, 'beds' => 2, 'rooms' => 2, 'description' => 'Two bedrooms and a lounge for families of four.'],
            ],
        ],
        'mida-creek-ocean-lodge-watamu' => [
            'provider' => 'coast',
            'destination' => 'watamu',
            'name' => 'Mida Creek Ocean Lodge',
            'address' => 'Turtle Bay Road',
            'description' => 'A timber-and-thatch lodge on the Watamu shoreline, with a curved pool and plunge pool on a wooden deck looking straight out to sea. Sunloungers line the deck, rooms have their own verandas, and the marine park’s coral gardens are a short boat ride away.',
            'cover' => '1566073771259-6a8506099945',
            'gallery' => ['1571896349842-33c89424de2d', '1651860282131-e3257674ccd1'],
            'stars' => 4,
            'rating' => 4.7,
            'reviews' => 168,
            'featured' => true,
            'amenities' => ['free-wi-fi', 'swimming-pool', 'air-conditioning', 'room-service', 'parking'],
            'room_types' => [
                ['name' => 'Lodge Room', 'price' => 16800, 'capacity' => 2, 'beds' => 1, 'rooms' => 8, 'description' => 'Double or twin with a private veranda.'],
                ['name' => 'Sea-view Suite', 'price' => 24500, 'capacity' => 3, 'beds' => 2, 'rooms' => 3, 'description' => 'Upper-floor suite with a sea-facing balcony.'],
            ],
        ],
        'mara-ridge-safari-lodge' => [
            'provider' => 'safari',
            'destination' => 'maasai-mara',
            'name' => 'Mara Ridge Safari Lodge',
            'address' => 'Oloololo Escarpment, Maasai Mara',
            'description' => 'A full-board safari lodge on the western escarpment of the Mara, with sweeping views over the plains. Rates include all meals and two shared game drives a day in open-sided vehicles; balloon flights and bush breakfasts can be added. Rooms are spacious, quiet and finished in warm neutral tones.',
            'cover' => '1516426122078-c23e76319801',
            'gallery' => ['1585402969048-86f38f8bfc2e', '1631049307264-da0ec9d70304'],
            'stars' => 5,
            'rating' => 4.9,
            'reviews' => 302,
            'featured' => true,
            'amenities' => ['free-wi-fi', 'swimming-pool', 'room-service', '247-reception', 'private-balcony'],
            'room_types' => [
                ['name' => 'Escarpment Room', 'price' => 38000, 'capacity' => 2, 'beds' => 1, 'rooms' => 10, 'description' => 'Full board for two, with a balcony over the plains.'],
                ['name' => 'Family Room', 'price' => 56000, 'capacity' => 4, 'beds' => 2, 'rooms' => 3, 'description' => 'Two interconnecting rooms, full board for four.'],
            ],
        ],
        'gion-lantern-hotel-kyoto' => [
            'provider' => 'world',
            'destination' => 'kyoto',
            'name' => 'Gion Lantern Hotel',
            'address' => 'Higashiyama-ku, Kyoto',
            'description' => 'A small hotel on the edge of Higashiyama, a short walk up the preserved wooden lanes to the Yasaka pagoda. Rooms mix warm timber and soft lighting with proper beds or tatami, and the team will book tea ceremonies, kaiseki dinners and early temple visits before the crowds arrive.',
            'cover' => '1493976040374-85c8e12f0c0e',
            'gallery' => ['1545569341-9eb8b30979d9', '1611892440504-42a792e24d32'],
            'stars' => 4,
            'rating' => 4.8,
            'reviews' => 389,
            'featured' => false,
            'amenities' => ['free-wi-fi', 'air-conditioning', '247-reception', 'work-desk'],
            'room_types' => [
                ['name' => 'Superior Twin', 'price' => 27500, 'capacity' => 2, 'beds' => 2, 'rooms' => 8, 'description' => 'Two single beds, rain shower and city view.'],
                ['name' => 'Tatami Suite', 'price' => 41000, 'capacity' => 3, 'beds' => 3, 'rooms' => 2, 'description' => 'Traditional tatami room with futons and a soaking tub.'],
            ],
        ],
    ];

    public const VILLAS = [
        'villa-kilindini-diani' => [
            'provider' => 'coast',
            'destination' => 'diani-beach',
            'name' => 'Villa Kilindini',
            'address' => 'Galu Kinondo, Diani',
            'description' => 'A whitewashed Swahili-style villa with arched verandas, a private pool and a garden full of palms and bougainvillea, a few minutes’ walk from the beach. Four en-suite bedrooms, a resident chef and floating breakfasts in the pool on request.',
            'cover' => '1708119063168-4785d1359824',
            'gallery' => ['1586861635167-e5223aadc9fe', '1582719508461-905c673771fd'],
            'price' => 48000,
            'bedrooms' => 4,
            'bathrooms' => 4,
            'max_guests' => 8,
            'pool' => true,
            'rating' => 4.9,
            'reviews' => 57,
            'featured' => true,
            'amenities' => ['private-pool', 'chef-service', 'garden', 'free-wi-fi', 'air-conditioning', 'private-parking'],
        ],
        'watamu-white-house-villa' => [
            'provider' => 'coast',
            'destination' => 'watamu',
            'name' => 'Watamu White House',
            'address' => 'Jacaranda Road, Watamu',
            'description' => 'A crisp, modern two-storey villa with floor-to-ceiling glass, a long private pool and a roof terrace. Three bedrooms, open-plan living that opens onto the pool deck, and the beach a short walk away.',
            'cover' => '1613490493576-7fde63acd811',
            'gallery' => ['1600596542815-ffad4c1539a9'],
            'price' => 39500,
            'bedrooms' => 3,
            'bathrooms' => 3,
            'max_guests' => 6,
            'pool' => true,
            'rating' => 4.7,
            'reviews' => 41,
            'featured' => false,
            'amenities' => ['private-pool', 'bbq-area', 'free-wi-fi', 'air-conditioning', 'private-parking'],
        ],
        'shela-dune-house-lamu' => [
            'provider' => 'coast',
            'destination' => 'lamu-island',
            'name' => 'Shela Dune House',
            'address' => 'Shela Village, Lamu Island',
            'description' => 'A thatched beach house at the edge of Shela’s dunes, with a wall of windows opening onto a wooden deck and the channel beyond. Three bedrooms, a house cook and a dhow captain on call for sunset sails.',
            'cover' => '1602002418082-a4443e081dd1',
            'gallery' => ['1664093671658-a2aac3a344a9'],
            'price' => 34000,
            'bedrooms' => 3,
            'bathrooms' => 2,
            'max_guests' => 6,
            'pool' => false,
            'rating' => 4.8,
            'reviews' => 63,
            'featured' => true,
            'amenities' => ['ocean-view', 'chef-service', 'free-wi-fi', 'garden'],
        ],
        'casa-belvedere-vernazza' => [
            'provider' => 'world',
            'destination' => 'cinque-terre',
            'name' => 'Casa Belvedere',
            'address' => 'Via Visconti, Vernazza',
            'description' => 'A tall, narrow village house above Vernazza’s harbour, with views over the pastel rooftops to the sea. Two bedrooms, a classic Italian-style master bedroom, a small kitchen and a terrace for evening aperitivo. The coastal path starts at the door.',
            'cover' => '1534445867742-43195f401b6c',
            'gallery' => ['1505693416388-ac5ce068fe85'],
            'price' => 31000,
            'bedrooms' => 2,
            'bathrooms' => 2,
            'max_guests' => 4,
            'pool' => false,
            'rating' => 4.6,
            'reviews' => 88,
            'featured' => false,
            'amenities' => ['ocean-view', 'free-wi-fi', 'air-conditioning'],
        ],
    ];

    public const BED_AND_BREAKFASTS = [
        'lamu-old-town-courtyard-bnb' => [
            'provider' => 'coast',
            'destination' => 'lamu-island',
            'name' => 'Old Town Courtyard B&B',
            'address' => 'Harambee Avenue, Lamu Old Town',
            'description' => 'Six rooms around a cool inner courtyard in a restored Swahili townhouse, with dark carved wood, crisp linen and a rooftop where breakfast is served looking over the old town and the channel.',
            'cover' => '1611892440504-42a792e24d32',
            'gallery' => ['1664093671658-a2aac3a344a9'],
            'price' => 8900,
            'rooms' => 6,
            'max_guests' => 2,
            'rating' => 4.7,
            'reviews' => 132,
            'featured' => false,
            'amenities' => ['free-wi-fi', 'air-conditioning', 'garden'],
        ],
        'kimana-acacia-bnb-amboseli' => [
            'provider' => 'safari',
            'destination' => 'amboseli',
            'name' => 'Kimana Acacia B&B',
            'address' => 'Kimana, Amboseli',
            'description' => 'A friendly guesthouse on the plains outside Amboseli with Kilimanjaro on the horizon. Five simple, comfortable rooms, a big breakfast before the morning game drive and sundowners in the acacia garden.',
            'cover' => '1489392191049-fc10c97e64b6',
            'gallery' => ['1613061445510-e296bfedb73e'],
            'price' => 11500,
            'rooms' => 5,
            'max_guests' => 3,
            'rating' => 4.6,
            'reviews' => 74,
            'featured' => false,
            'amenities' => ['free-wi-fi', 'garden', 'parking'],
        ],
        'nanyuki-highland-farmhouse-bnb' => [
            'provider' => 'safari',
            'destination' => 'mount-kenya',
            'name' => 'Nanyuki Highland Farmhouse',
            'address' => 'Timau Road, Nanyuki',
            'description' => 'A working farm on the slopes below Mount Kenya with four cosy bedrooms in the old farmhouse, log fires in the evening and a farm breakfast before hikes up to the moorlands.',
            'cover' => '1505693416388-ac5ce068fe85',
            'gallery' => ['1707819487421-21228af983a9'],
            'price' => 7800,
            'rooms' => 4,
            'max_guests' => 3,
            'rating' => 4.5,
            'reviews' => 59,
            'featured' => false,
            'amenities' => ['free-wi-fi', 'garden', 'parking', 'pet-friendly'],
        ],
        'erg-chebbi-desert-camp' => [
            'provider' => 'world',
            'destination' => 'marrakech-sahara',
            'name' => 'Erg Chebbi Desert Camp',
            'address' => 'Merzouga, Erg Chebbi',
            'city' => 'Merzouga',
            'description' => 'Ten canvas tents pitched among the dunes of Erg Chebbi, lit by lanterns after dark. Dinner and breakfast are included, you arrive by camel at sunset, and the sky at night is extraordinary.',
            'cover' => '1613169620329-6785c004d900',
            'gallery' => ['1559586616-361e18714958'],
            'price' => 13200,
            'rooms' => 10,
            'max_guests' => 2,
            'rating' => 4.8,
            'reviews' => 211,
            'featured' => true,
            'amenities' => ['free-wi-fi'],
        ],
    ];

    public function run(): void
    {
        if (Amenity::query()->count() === 0) {
            $this->call(AmenitySeeder::class);
        }

        $reviewer = ShowcaseAccountSeeder::reviewer();
        $publisher = app(AccommodationPublicationService::class);
        $published = 0;

        foreach (self::HOTELS as $slug => $data) {
            $hotel = $this->upsert(Hotel::class, $slug, $data, [
                'stars' => $data['stars'],
                'avg_price_per_night' => (float) min(array_column($data['room_types'], 'price')),
            ]);

            foreach (array_values($data['room_types']) as $typeIndex => $type) {
                $roomType = $hotel->roomTypes()->updateOrCreate(
                    ['slug' => $slug.'-'.\Illuminate\Support\Str::slug($type['name'])],
                    [
                        'name' => $type['name'],
                        'description' => $type['description'],
                        'price_per_night' => $type['price'],
                        'currency' => 'KES',
                        'capacity' => $type['capacity'],
                        'beds' => $type['beds'],
                        'hero_image_url' => ShowcaseDestinationSeeder::photo($data['gallery'][1] ?? $data['cover'], 1200),
                    ],
                );

                for ($i = 1; $i <= $type['rooms']; $i++) {
                    $hotel->rooms()->firstOrCreate(
                        ['room_type_id' => $roomType->id, 'name' => $type['name'].' '.$i],
                        [
                            'room_number' => ($typeIndex + 1) * 100 + $i,
                            'description' => $type['description'],
                            'capacity' => $type['capacity'],
                            'beds' => $type['beds'],
                            'bed_type' => match (true) {
                                $type['capacity'] >= 4 => 'double', $type['beds'] > 1 => 'single', default => 'king'
                            },
                            'status' => 'available',
                            'is_available' => true,
                            'has_ac' => in_array('air-conditioning', $data['amenities'], true),
                            'has_wifi' => true,
                        ],
                    );
                }
            }

            $published += $this->publish($hotel, $data, $publisher, $reviewer->id);
        }

        foreach (self::VILLAS as $slug => $data) {
            $villa = $this->upsert(Villa::class, $slug, $data, [
                'avg_price_per_night' => (float) $data['price'],
                'bedrooms' => $data['bedrooms'],
                'bathrooms' => $data['bathrooms'],
                'max_guests' => $data['max_guests'],
                'has_private_pool' => $data['pool'],
            ]);
            $published += $this->publish($villa, $data, $publisher, $reviewer->id);
        }

        foreach (self::BED_AND_BREAKFASTS as $slug => $data) {
            $bnb = $this->upsert(BedAndBreakfast::class, $slug, $data, [
                'price_per_night' => $data['price'],
                'rooms' => $data['rooms'],
                'max_guests' => $data['max_guests'],
                'has_breakfast' => true,
            ]);
            $published += $this->publish($bnb, $data, $publisher, $reviewer->id);
        }

        $this->command?->info(sprintf(
            'Showcase stays ready: %d hotels, %d villas, %d B&Bs (%d newly published).',
            count(self::HOTELS), count(self::VILLAS), count(self::BED_AND_BREAKFASTS), $published,
        ));
    }

    /**
     * All showcase stays as [morph alias, slug] pairs, in a stable order.
     *
     * @return array<int, array{0: string, 1: string}>
     */
    public static function staySlugs(): array
    {
        return array_merge(
            array_map(fn ($slug) => ['hotel', $slug], array_keys(self::HOTELS)),
            array_map(fn ($slug) => ['villa', $slug], array_keys(self::VILLAS)),
            array_map(fn ($slug) => ['bed_and_breakfast', $slug], array_keys(self::BED_AND_BREAKFASTS)),
        );
    }

    /**
     * Published canonical rows for the showcase stays, keyed by property slug.
     *
     * @return \Illuminate\Support\Collection<string, Accommodation>
     */
    public static function accommodations()
    {
        return Accommodation::query()
            ->published()
            ->with('bookable')
            ->whereIn('slug', array_column(self::staySlugs(), 1))
            ->get()
            ->keyBy('slug');
    }

    /**
     * @param  class-string<Model>  $class
     */
    private function upsert(string $class, string $slug, array $data, array $typeAttributes): Model
    {
        $provider = ShowcaseProviderSeeder::provider($data['provider']);
        $destination = ShowcaseDestinationSeeder::destination($data['destination']);

        /** @var Model $property */
        $query = method_exists($class, 'withTrashed') ? $class::withTrashed() : $class::query();
        $property = $query->firstOrNew(['slug' => $slug]);
        if (method_exists($property, 'trashed') && $property->trashed()) {
            $property->restore();
        }
        $property->forceFill(array_merge([
            'provider_id' => $provider->id,
            'name' => $data['name'],
            'description' => $data['description'],
            'address' => $data['address'],
            'city' => $data['city'] ?? $destination->city,
            'country' => $destination->country,
            'latitude' => (float) $destination->latitude,
            'longitude' => (float) $destination->longitude,
            'policies' => self::POLICIES,
            'cover_image' => ShowcaseDestinationSeeder::photo($data['cover']),
            'gallery' => array_map(fn (string $id) => ShowcaseDestinationSeeder::photo($id), array_merge([$data['cover']], $data['gallery'])),
            'is_featured' => $data['featured'],
            'is_active' => true,
            'avg_rating' => $data['rating'],
            'reviews_count' => $data['reviews'],
        ], $typeAttributes));

        // Not every property model casts its numeric columns, and an uncast
        // numeric string ("4.90") always looks dirty against 4.9. A spurious
        // save would demote a published listing to draft, so keep originals
        // that are numerically equal.
        foreach (['latitude', 'longitude', 'avg_rating', 'avg_price_per_night', 'price_per_night'] as $numeric) {
            if ($property->exists && $property->isDirty($numeric)
                && is_numeric($property->getRawOriginal($numeric))
                && (float) $property->getRawOriginal($numeric) === (float) $property->getAttributes()[$numeric]) {
                $property->setRawAttributes(
                    array_merge($property->getAttributes(), [$numeric => $property->getRawOriginal($numeric)])
                );
            }
        }

        if (! $property->exists || $property->isDirty()) {
            $property->save();
        }

        $amenityIds = Amenity::query()->whereIn('slug', $data['amenities'])->pluck('id')->all();
        $property->amenities()->sync($amenityIds);

        // The canonical sync does not carry the destination, and keeps the
        // provider it was first created with; align both explicitly.
        $canonical = $property->accommodation()->firstOrFail();
        $canonical->forceFill([
            'destination_id' => $destination->id,
            'provider_id' => $provider->id,
        ]);
        if ($canonical->isDirty()) {
            $canonical->save();
        }

        return $property;
    }

    /**
     * Run the listing through the provider -> admin publication workflow.
     *
     * @return int 1 when the listing was (re)published by this call
     */
    private function publish(Model $property, array $data, AccommodationPublicationService $publisher, int $reviewerId): int
    {
        $canonical = $property->accommodation()->firstOrFail();

        if ($canonical->isPublished()) {
            return 0;
        }

        if (in_array($canonical->status, [AccommodationStatus::Suspended, AccommodationStatus::Archived], true)) {
            $canonical->forceFill([
                'status' => AccommodationStatus::Draft,
                'verification_status' => VerificationStatus::Unverified,
                'suspended_at' => null,
                'suspension_reason' => null,
            ])->save();
        }

        if (in_array($canonical->status, [AccommodationStatus::Draft, AccommodationStatus::Rejected], true)) {
            $canonical = $publisher->submitForReview($canonical);
        }

        $publisher->approve($canonical, $reviewerId);

        return 1;
    }
}
