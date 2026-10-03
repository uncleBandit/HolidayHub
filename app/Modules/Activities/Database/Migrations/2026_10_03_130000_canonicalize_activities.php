<?php

use App\Modules\Activities\Domain\Models\Activity;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    private const LEGACY_EXPERIENCE_TYPE = 'experience';

    private const EXPERIENCE_CLASS = 'App\\Modules\\Activities\\Domain\\Models\\Experience';

    private const MORPH_TABLES = [
        ['table' => 'bookings', 'type' => 'bookable_type', 'id' => 'bookable_id'],
        ['table' => 'reviews', 'type' => 'reviewable_type', 'id' => 'reviewable_id'],
        ['table' => 'availabilities', 'type' => 'bookable_type', 'id' => 'bookable_id'],
        ['table' => 'seasonal_rates', 'type' => 'seasonal_rateable_type', 'id' => 'seasonal_rateable_id'],
        ['table' => 'offers', 'type' => 'offerable_type', 'id' => 'offerable_id'],
        ['table' => 'images', 'type' => 'imageable_type', 'id' => 'imageable_id'],
        ['table' => 'amenables', 'type' => 'amenable_type', 'id' => 'amenable_id'],
        ['table' => 'media_posts', 'type' => 'targetable_type', 'id' => 'targetable_id'],
    ];

    public function up(): void
    {
        Schema::create('activity_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('activity_categories')->nullOnDelete();
            $table->string('name');
            $table->string('slug')->unique();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('destination_id')
                ->constrained('activity_categories')->nullOnDelete();
            $table->string('status', 32)->default('draft')->index();
            $table->string('verification_status', 32)->default('unverified')->index();
            $table->string('booking_mode', 24)->default('shared');
            $table->string('timezone', 64)->default('UTC');
            $table->string('short_description', 500)->nullable();
            $table->json('highlights')->nullable();
            $table->json('age_policy')->nullable();
            $table->json('attributes')->nullable();
            $table->json('inclusions')->nullable();
            $table->json('exclusions')->nullable();
            $table->json('accessibility')->nullable();
            $table->unsignedInteger('booking_cutoff_minutes')->nullable();
            $table->unsignedInteger('minimum_notice_minutes')->default(0);
            $table->boolean('weather_dependent')->default(false);
            $table->text('weather_cancellation_policy')->nullable();
            $table->text('safety_instructions')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->text('suspension_reason')->nullable();
            $table->unsignedInteger('included_participants')->default(1);
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('published_at')->nullable();
            $table->timestampTz('verified_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('legacy_experience_id')->nullable()->unique();
        });

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['provider_id']);
            $table->foreignId('provider_id')->nullable()->change();
            $table->foreign('provider_id')->references('id')->on('providers')->nullOnDelete();
            $table->dropForeign(['destination_id']);
            $table->foreignId('destination_id')->nullable()->change();
            $table->foreign('destination_id')->references('id')->on('destinations')->nullOnDelete();
        });

        Schema::create('activity_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reviewer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32);
            $table->timestampTz('submitted_at')->nullable();
            $table->timestampTz('reviewed_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('provider_documents')->nullable();
            $table->json('checks')->nullable();
            $table->timestamps();
            $table->index(['activity_id', 'created_at']);
        });

        Schema::create('activity_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->decimal('base_price', 10, 2);
            $table->string('currency', 3);
            $table->unsignedInteger('included_participants')->default(1);
            $table->unsignedInteger('max_participants')->nullable();
            $table->string('booking_mode', 24)->default('shared');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['activity_id', 'slug']);
        });

        Schema::create('activity_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_option_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->string('timezone', 64);
            $table->unsignedInteger('capacity');
            $table->date('active_from')->nullable();
            $table->date('active_until')->nullable();
            $table->unsignedInteger('booking_cutoff_minutes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['activity_id', 'is_active', 'day_of_week']);
        });

        Schema::create('activity_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->foreignId('activity_option_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('activity_schedule_id')->nullable()->constrained()->nullOnDelete();
            $table->timestampTz('starts_at');
            $table->timestampTz('ends_at');
            $table->string('timezone', 64);
            $table->unsignedInteger('capacity');
            $table->unsignedInteger('booked_capacity')->default(0);
            $table->string('status', 24)->default('scheduled');
            $table->timestampTz('booking_cutoff_at')->nullable();
            $table->timestamps();
            $table->unique(['activity_schedule_id', 'starts_at']);
            $table->index(['activity_id', 'status', 'starts_at']);
        });

        Schema::create('activity_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('type', 24);
            $table->string('name');
            $table->string('address')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->text('instructions')->nullable();
            $table->unsignedSmallInteger('sequence')->default(0);
            $table->timestamps();
            $table->index(['activity_id', 'type', 'sequence']);
        });

        Schema::create('activity_itinerary_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_minutes')->nullable();
            $table->timestamps();
            $table->unique(['activity_id', 'sequence']);
        });

        Schema::create('activity_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32)->default('general');
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('required')->default(true);
            $table->timestamps();
            $table->index(['activity_id', 'required']);
        });

        Schema::create('activity_languages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 12);
            $table->timestamps();
            $table->unique(['activity_id', 'language_code']);
        });

        $this->backfillCategories();
        $this->preserveExistingActivities();
        $this->canonicalizeActivityRelations();
        $this->mergeExperiences();
        $this->migrateLegacyImages();
    }

    public function down(): void
    {
        $this->restoreExperiences();

        Schema::dropIfExists('activity_languages');
        Schema::dropIfExists('activity_requirements');
        Schema::dropIfExists('activity_itinerary_items');
        Schema::dropIfExists('activity_locations');
        Schema::dropIfExists('activity_sessions');
        Schema::dropIfExists('activity_schedules');
        Schema::dropIfExists('activity_options');
        Schema::dropIfExists('activity_verifications');

        Schema::table('activities', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['reviewed_by']);
            $table->dropUnique(['legacy_experience_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['verification_status']);
            $table->dropColumn([
                'category_id',
                'status',
                'verification_status',
                'booking_mode',
                'timezone',
                'short_description',
                'highlights',
                'age_policy',
                'attributes',
                'inclusions',
                'exclusions',
                'accessibility',
                'booking_cutoff_minutes',
                'minimum_notice_minutes',
                'weather_dependent',
                'weather_cancellation_policy',
                'safety_instructions',
                'rejection_reason',
                'suspension_reason',
                'included_participants',
                'submitted_at',
                'published_at',
                'verified_at',
                'reviewed_by',
                'legacy_experience_id',
            ]);
        });

        Schema::dropIfExists('activity_categories');
    }

    private function backfillCategories(): void
    {
        DB::table('activities')->whereNotNull('type')->distinct()->pluck('type')->each(function (string $name): void {
            $categoryId = $this->categoryId($name);

            DB::table('activities')->where('type', $name)->update(['category_id' => $categoryId]);
        });
    }

    private function categoryId(string $name, bool $legacy = false): int
    {
        $baseSlug = Str::slug($name) ?: 'experiences';
        $categoryId = DB::table('activity_categories')->where('slug', $baseSlug)->value('id');
        if ($categoryId) {
            return (int) $categoryId;
        }

        $slug = $legacy ? $baseSlug.'-legacy' : $baseSlug;
        $suffix = 2;
        while (DB::table('activity_categories')->where('slug', $slug)->exists()) {
            $slug = ($legacy ? $baseSlug.'-legacy' : $baseSlug).'-'.$suffix++;
        }

        return DB::table('activity_categories')->insertGetId([
            'name' => $name,
            'slug' => $slug,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function canonicalizeActivityRelations(): void
    {
        foreach (self::MORPH_TABLES as $relation) {
            if (! Schema::hasTable($relation['table'])
                || ! Schema::hasColumn($relation['table'], $relation['type'])) {
                continue;
            }

            DB::table($relation['table'])
                ->where($relation['type'], Activity::class)
                ->update([$relation['type'] => 'activity']);
        }
    }

    private function preserveExistingActivities(): void
    {
        DB::table('activities')->orderBy('id')->chunkById(250, function ($activities): void {
            foreach ($activities as $activity) {
                $status = $activity->is_active ? 'published' : 'archived';
                DB::table('activities')->where('id', $activity->id)->update([
                    'status' => $status,
                    'verification_status' => $activity->is_active ? 'approved' : 'unverified',
                    'published_at' => $activity->is_active ? ($activity->updated_at ?? now()) : null,
                    'verified_at' => $activity->is_active ? ($activity->updated_at ?? now()) : null,
                ]);

                if ($activity->is_active) {
                    DB::table('activity_verifications')->insert([
                        'activity_id' => $activity->id,
                        'status' => 'approved',
                        'reviewed_at' => $activity->updated_at ?? now(),
                        'notes' => 'Existing public activity preserved during lifecycle migration.',
                        'checks' => json_encode(['source' => 'legacy_is_active']),
                        'created_at' => $activity->updated_at ?? now(),
                        'updated_at' => $activity->updated_at ?? now(),
                    ]);
                }
            }
        });
    }

    private function mergeExperiences(): void
    {
        if (! Schema::hasTable('experiences')) {
            return;
        }

        DB::table('experiences')->orderBy('id')->chunkById(250, function ($experiences): void {
            foreach ($experiences as $experience) {
                $destination = null;
                if ($experience->city) {
                    $destination = DB::table('destinations')
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($experience->city)])
                        ->orWhereRaw('LOWER(city) = ?', [mb_strtolower($experience->city)])
                        ->value('id');
                }

                $categoryName = $experience->category ?: 'Experiences';
                $categoryId = $this->categoryId($categoryName, legacy: true);

                $slugBase = Str::slug($experience->title) ?: 'experience';
                $slug = $slugBase.'-'.$experience->id;
                $duration = $this->durationInMinutes($experience->duration);
                $activityId = DB::table('activities')->insertGetId([
                    'provider_id' => $experience->provider_id,
                    'destination_id' => $destination,
                    'category_id' => $categoryId,
                    'name' => $experience->title,
                    'slug' => $slug,
                    'type' => $categoryName,
                    'description' => $experience->description,
                    'short_description' => $experience->description ? Str::limit($experience->description, 250) : null,
                    'thumbnail' => $experience->cover_image,
                    'base_price' => $experience->price,
                    'currency' => $experience->currency ?: 'USD',
                    'duration_minutes' => $duration,
                    'capacity' => $experience->max_guests ?? $experience->capacity,
                    'included_participants' => max(1, (int) $experience->included_guests),
                    'is_featured' => (bool) $experience->featured,
                    'is_active' => false,
                    'status' => 'draft',
                    'verification_status' => 'unverified',
                    'booking_mode' => 'shared',
                    'timezone' => 'UTC',
                    'legacy_experience_id' => $experience->id,
                    'rating' => 0,
                    'reviews_count' => 0,
                    'bookings_count' => 0,
                    'created_at' => $experience->created_at ?? now(),
                    'updated_at' => $experience->updated_at ?? now(),
                ]);

                if ($experience->city || $experience->location) {
                    DB::table('activity_locations')->insert([
                        'activity_id' => $activityId,
                        'type' => 'meeting_point',
                        'name' => $experience->city ?: $experience->location,
                        'address' => $experience->location,
                        'sequence' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->remapExperienceRelations((int) $experience->id, $activityId);
            }
        });

        Schema::drop('experiences');
    }

    private function migrateLegacyImages(): void
    {
        if (! Schema::hasTable('images')) {
            return;
        }

        DB::table('activities')->whereNull('legacy_experience_id')->orderBy('id')->chunkById(250, function ($activities): void {
            foreach ($activities as $activity) {
                $paths = array_values(array_filter(array_merge(
                    [$activity->thumbnail],
                    is_array($activity->gallery) ? $activity->gallery : (json_decode($activity->gallery ?? '[]', true) ?: [])
                )));
                $paths = array_values(array_unique($paths));
                if ($paths === []) {
                    continue;
                }

                foreach ($paths as $order => $path) {
                    $exists = DB::table('images')
                        ->where('imageable_type', 'activity')
                        ->where('imageable_id', $activity->id)
                        ->where('path', $path)
                        ->exists();
                    if (! $exists) {
                        DB::table('images')->insert([
                            'imageable_type' => 'activity',
                            'imageable_id' => $activity->id,
                            'path' => $path,
                            'disk' => 'public',
                            'order' => $order,
                            'is_primary' => $order === 0,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }
            }
        });
    }

    private function remapExperienceRelations(int $experienceId, int $activityId): void
    {
        foreach (self::MORPH_TABLES as $relation) {
            if (! Schema::hasTable($relation['table'])
                || ! Schema::hasColumn($relation['table'], $relation['type'])
                || ! Schema::hasColumn($relation['table'], $relation['id'])) {
                continue;
            }

            DB::table($relation['table'])
                ->whereIn($relation['type'], [self::LEGACY_EXPERIENCE_TYPE, self::EXPERIENCE_CLASS])
                ->where($relation['id'], $experienceId)
                ->update([$relation['type'] => 'activity', $relation['id'] => $activityId]);
        }
    }

    private function restoreExperiences(): void
    {
        if (! Schema::hasTable('activities')
            || ! Schema::hasColumn('activities', 'legacy_experience_id')) {
            return;
        }

        Schema::create('experiences', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('city')->nullable();
            $table->string('location')->nullable();
            $table->string('category')->nullable();
            $table->string('duration')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->string('currency', 3)->default('USD');
            $table->unsignedInteger('capacity')->nullable();
            $table->unsignedInteger('included_guests')->default(1);
            $table->unsignedInteger('max_guests')->nullable();
            $table->string('cover_image')->nullable();
            $table->boolean('featured')->default(false);
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        $migratedIds = [];
        DB::table('activities')->whereNotNull('legacy_experience_id')->orderBy('id')->get()->each(function ($activity) use (&$migratedIds): void {
            $legacyId = (int) $activity->legacy_experience_id;
            $migratedIds[$activity->id] = $legacyId;

            $meetingPoint = DB::table('activity_locations')
                ->where('activity_id', $activity->id)
                ->where('type', 'meeting_point')
                ->first();

            DB::table('experiences')->insert([
                'id' => $legacyId,
                'title' => $activity->name,
                'description' => $activity->description,
                'city' => $meetingPoint?->name,
                'location' => $meetingPoint?->address,
                'category' => $activity->type,
                'duration' => $activity->duration_minutes ? (string) $activity->duration_minutes : null,
                'price' => $activity->base_price ?? 0,
                'currency' => $activity->currency ?? 'USD',
                'capacity' => $activity->capacity,
                'included_guests' => $activity->included_participants,
                'max_guests' => $activity->capacity,
                'cover_image' => $activity->thumbnail,
                'featured' => $activity->is_featured,
                'provider_id' => $activity->provider_id,
                'created_at' => $activity->created_at,
                'updated_at' => $activity->updated_at,
            ]);
        });

        foreach (self::MORPH_TABLES as $relation) {
            if (! Schema::hasTable($relation['table'])
                || ! Schema::hasColumn($relation['table'], $relation['type'])
                || ! Schema::hasColumn($relation['table'], $relation['id'])) {
                continue;
            }

            foreach ($migratedIds as $activityId => $experienceId) {
                DB::table($relation['table'])
                    ->where($relation['type'], 'activity')
                    ->where($relation['id'], $activityId)
                    ->update([$relation['type'] => self::LEGACY_EXPERIENCE_TYPE, $relation['id'] => $experienceId]);
            }
        }

        if ($migratedIds !== []) {
            DB::table('activities')->whereIn('id', array_keys($migratedIds))->delete();
        }
    }

    private function durationInMinutes(?string $duration): ?int
    {
        if (! $duration) {
            return null;
        }

        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*(minutes?|mins?|hours?|hrs?|days?)?\s*$/i', $duration, $matches) !== 1) {
            return null;
        }

        $quantity = (float) $matches[1];
        $unit = strtolower($matches[2] ?? 'hours');

        return max(1, (int) round($quantity * match (true) {
            str_starts_with($unit, 'day') => 1440,
            str_starts_with($unit, 'hour'), str_starts_with($unit, 'hr') => 60,
            default => 1,
        }));
    }
};
