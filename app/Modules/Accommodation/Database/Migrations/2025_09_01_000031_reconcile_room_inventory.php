<?php

use App\Modules\Accommodation\Domain\Models\RoomType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('status', 32)->default('available');
            $table->integer('legacy_room_number')->nullable();
        });

        DB::table('rooms')->where('is_available', false)->update(['status' => 'maintenance']);

        $duplicateNumbers = DB::table('rooms')
            ->select('hotel_id', 'room_number')
            ->groupBy('hotel_id', 'room_number')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($duplicateNumbers as $duplicate) {
            $roomIds = DB::table('rooms')
                ->where('hotel_id', $duplicate->hotel_id)
                ->where('room_number', $duplicate->room_number)
                ->orderBy('id')
                ->pluck('id');
            $nextNumber = (int) DB::table('rooms')
                ->where('hotel_id', $duplicate->hotel_id)
                ->max('room_number') + 1;

            foreach ($roomIds->skip(1) as $roomId) {
                DB::table('rooms')->where('id', $roomId)->update([
                    'legacy_room_number' => $duplicate->room_number,
                    'room_number' => $nextNumber++,
                ]);
            }
        }

        Schema::table('rooms', function (Blueprint $table) {
            $table->unique(['hotel_id', 'room_number'], 'rooms_hotel_room_number_unique');
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->json('legacy_amenities')->nullable();
        });

        DB::table('room_types')->orderBy('id')->chunk(250, function ($roomTypes) {
            foreach ($roomTypes as $roomType) {
                if ($roomType->amenities === null) {
                    continue;
                }

                DB::table('room_types')->where('id', $roomType->id)->update([
                    'legacy_amenities' => $roomType->amenities,
                ]);

                $amenityNames = json_decode($roomType->amenities, true);
                if (! is_array($amenityNames)) {
                    continue;
                }

                foreach ($amenityNames as $amenityName) {
                    $amenity = is_numeric($amenityName)
                        ? DB::table('amenities')->where('id', $amenityName)->first()
                        : DB::table('amenities')->whereRaw('LOWER(name) = ?', [mb_strtolower((string) $amenityName)])->first();

                    if ($amenity) {
                        DB::table('amenables')->insertOrIgnore([
                            'amenity_id' => $amenity->id,
                            'amenable_type' => (new RoomType)->getMorphClass(),
                            'amenable_id' => $roomType->id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('amenities');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->json('amenities')->nullable();
        });

        DB::table('room_types')->orderBy('id')->chunk(250, function ($roomTypes) {
            foreach ($roomTypes as $roomType) {
                if ($roomType->legacy_amenities !== null) {
                    DB::table('room_types')->where('id', $roomType->id)->update([
                        'amenities' => $roomType->legacy_amenities,
                    ]);
                }
            }
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('legacy_amenities');
        });

        DB::table('rooms')->whereNotNull('legacy_room_number')->update([
            'room_number' => DB::raw('legacy_room_number'),
        ]);

        Schema::table('rooms', function (Blueprint $table) {
            $table->dropUnique('rooms_hotel_room_number_unique');
            $table->dropColumn(['status', 'legacy_room_number']);
        });
    }
};
