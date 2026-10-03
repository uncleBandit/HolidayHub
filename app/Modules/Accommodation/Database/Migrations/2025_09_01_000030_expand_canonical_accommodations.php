<?php

use App\Modules\Accommodation\Domain\Models\BedAndBreakfast;
use App\Modules\Accommodation\Domain\Models\Hotel;
use App\Modules\Accommodation\Domain\Models\Villa;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const PROPERTY_TABLES = [
        'hotels' => ['type' => 'hotel', 'class' => Hotel::class, 'price' => 'avg_price_per_night'],
        'villas' => ['type' => 'villa', 'class' => Villa::class, 'price' => 'avg_price_per_night'],
        'bed_and_breakfasts' => ['type' => 'bed_and_breakfast', 'class' => BedAndBreakfast::class, 'price' => 'price_per_night'],
    ];

    public function up(): void
    {
        Schema::table('accommodations', function (Blueprint $table) {
            $table->string('status', 32)->default('draft');
            $table->string('verification_status', 32)->default('unverified');
            $table->string('booking_mode', 32)->default('instant');
            $table->string('name')->nullable();
            $table->string('slug')->nullable()->index();
            $table->text('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable()->index();
            $table->string('country')->nullable()->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->json('policies')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->index(['status', 'verification_status']);
            $table->index(['provider_id', 'status']);
        });

        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropForeign(['destination_id']);
            $table->unsignedBigInteger('destination_id')->nullable()->change();
            $table->foreign('destination_id')->references('id')->on('destinations')->nullOnDelete();
        });

        foreach (self::PROPERTY_TABLES as $table => $mapping) {
            DB::table($table)->orderBy('id')->chunk(250, function ($properties) use ($mapping) {
                foreach ($properties as $property) {
                    $property = (array) $property;
                    $typeNames = [$mapping['type'], $mapping['class']];
                    $existing = DB::table('accommodations')
                        ->whereIn('bookable_type', $typeNames)
                        ->where('bookable_id', $property['id'])
                        ->first();

                    $isActive = (bool) ($property['is_active'] ?? true);
                    $isVerified = (bool) ($property['is_verified'] ?? false);
                    $status = ! $isActive ? 'suspended' : ($isVerified ? 'published' : 'draft');
                    $verificationStatus = $isVerified ? 'approved' : 'unverified';
                    $destinationId = $existing?->destination_id
                        ?? ($property['destination_id'] ?? null)
                        ?? $this->matchingDestinationId($property);

                    $attributes = [
                        'destination_id' => $destinationId,
                        'provider_id' => $existing?->provider_id ?? $property['provider_id'],
                        'bookable_type' => $mapping['type'],
                        'bookable_id' => $property['id'],
                        'is_featured' => (bool) ($property['is_featured'] ?? false),
                        'avg_price_per_night' => $property[$mapping['price']] ?? null,
                        'avg_rating' => $property['avg_rating'] ?? 0,
                        'reviews_count' => $property['reviews_count'] ?? 0,
                        'status' => $status,
                        'verification_status' => $verificationStatus,
                        'name' => $property['name'],
                        'slug' => $property['slug'] ?? null,
                        'description' => $property['description'] ?? null,
                        'address' => $property['address'] ?? null,
                        'city' => $property['city'] ?? null,
                        'country' => $property['country'] ?? null,
                        'latitude' => $property['latitude'] ?? null,
                        'longitude' => $property['longitude'] ?? null,
                        'policies' => $property['policies'] ?? null,
                        'verified_at' => $isVerified ? ($property['updated_at'] ?? now()) : null,
                        'published_at' => $status === 'published' ? ($property['updated_at'] ?? now()) : null,
                        'created_at' => $property['created_at'] ?? now(),
                        'updated_at' => $property['updated_at'] ?? now(),
                    ];

                    if ($existing) {
                        DB::table('accommodations')->where('id', $existing->id)->update($attributes);
                    } else {
                        DB::table('accommodations')->insert($attributes);
                    }
                }
            });
        }

        Schema::create('accommodation_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accommodation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32);
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['accommodation_id', 'created_at']);
        });

        foreach (['hotels' => Hotel::class, 'villas' => Villa::class, 'bed_and_breakfasts' => BedAndBreakfast::class] as $table => $class) {
            DB::table('accommodations')
                ->where('bookable_type', $this->typeFor($class))
                ->where('verification_status', 'approved')
                ->orderBy('id')
                ->chunkById(250, function ($accommodations) {
                    foreach ($accommodations as $accommodation) {
                        DB::table('accommodation_verifications')->insert([
                            'accommodation_id' => $accommodation->id,
                            'status' => 'approved',
                            'reason' => 'Migrated from the legacy verified flag.',
                            'metadata' => json_encode(['source' => 'legacy_is_verified']),
                            'created_at' => $accommodation->verified_at ?? now(),
                            'updated_at' => $accommodation->verified_at ?? now(),
                        ]);
                    }
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('accommodation_verifications');

        Schema::table('accommodations', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropIndex(['status', 'verification_status']);
            $table->dropIndex(['provider_id', 'status']);
            $table->dropIndex(['slug']);
            $table->dropIndex(['city']);
            $table->dropIndex(['country']);
            $table->dropColumn([
                'status',
                'verification_status',
                'booking_mode',
                'name',
                'slug',
                'description',
                'address',
                'city',
                'country',
                'latitude',
                'longitude',
                'policies',
                'submitted_at',
                'verified_at',
                'published_at',
                'suspended_at',
                'suspension_reason',
                'reviewed_by',
            ]);
        });
    }

    private function matchingDestinationId(array $property): ?int
    {
        if (empty($property['city'])) {
            return null;
        }

        return DB::table('destinations')
            ->whereRaw('LOWER(city) = ?', [mb_strtolower($property['city'])])
            ->when(! empty($property['country']), fn ($query) => $query->whereRaw('LOWER(country) = ?', [mb_strtolower($property['country'])]))
            ->value('id');
    }

    private function typeFor(string $class): string
    {
        foreach (self::PROPERTY_TABLES as $mapping) {
            if ($mapping['class'] === $class) {
                return $mapping['type'];
            }
        }

        return $class;
    }
};
