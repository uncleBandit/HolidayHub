<?php

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Media\Domain\Enums\MediaAssetType;
use App\Modules\Media\Domain\Enums\MediaPostStatus;
use App\Modules\Media\Domain\Enums\MediaPostType;
use App\Modules\Media\Domain\Models\MediaPost;
use App\Modules\Media\Presentation\Livewire\MediaModeration;
use App\Modules\Media\Presentation\Livewire\Provider\MediaLibrary;
use App\Modules\Providers\Domain\Models\Provider;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function aFakeMp4(): UploadedFile
{
    return UploadedFile::fake()->createWithContent(
        'sunset.mp4',
        "\x00\x00\x00\x18ftypmp42\x00\x00\x00\x00mp42isom",
    );
}

function aMediaProvider(): array
{
    $user = User::factory()->create();
    $provider = Provider::factory()->create([
        'user_id' => $user->id,
        'company_name' => 'Coastal Escape',
        'active' => true,
    ]);

    return [$user, $provider];
}

function aMediaAdmin(): User
{
    $role = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('lets providers submit a reel for moderation without exposing it publicly', function () {
    Storage::fake('local');
    config(['media.disk' => 'local']);
    [$user, $provider] = aMediaProvider();

    Livewire::actingAs($user)
        ->test(MediaLibrary::class)
        ->set('type', MediaPostType::Reel->value)
        ->set('caption', 'A sunset from our beach')
        ->set('video', aFakeMp4())
        ->call('publish')
        ->assertHasNoErrors()
        ->assertSee('submitted for review');

    $post = MediaPost::query()->sole();

    expect($post->status)->toBe(MediaPostStatus::PendingReview)
        ->and($post->type)->toBe(MediaPostType::Reel)
        ->and($post->provider_id)->toBe($provider->id)
        ->and($post->asset(MediaAssetType::Original))->not->toBeNull();

    $this->get('/reels')->assertOk()->assertDontSee('A sunset from our beach');
});

it('publishes approved reels to the reel feed and provider media profile', function () {
    Storage::fake('local');
    config(['media.disk' => 'local']);
    [$providerUser, $provider] = aMediaProvider();
    $this->actingAs($providerUser);

    Livewire::test(MediaLibrary::class)
        ->set('type', 'reel')
        ->set('caption', 'A sunset from our beach')
        ->set('video', aFakeMp4())
        ->call('publish')
        ->assertHasNoErrors();

    $post = MediaPost::query()->sole();
    Livewire::actingAs(aMediaAdmin())
        ->test(MediaModeration::class)
        ->call('approve', $post->id);

    expect($post->refresh()->status)->toBe(MediaPostStatus::Published);

    $this->get('/reels')
        ->assertOk()
        ->assertSee('A sunset from our beach')
        ->assertSee('Coastal Escape');

    $this->get(route('media.providers.media', $provider))
        ->assertOk()
        ->assertSee('Coastal Escape')
        ->assertSee('A sunset from our beach');
});

it('lets admins reject a video with a reason without publishing it', function () {
    Storage::fake('local');
    config(['media.disk' => 'local']);
    [$providerUser] = aMediaProvider();

    Livewire::actingAs($providerUser)
        ->test(MediaLibrary::class)
        ->set('type', 'video')
        ->set('video', aFakeMp4())
        ->call('publish')
        ->assertHasNoErrors();

    $post = MediaPost::query()->sole();

    Livewire::actingAs(aMediaAdmin())
        ->test(MediaModeration::class)
        ->set('rejectionReason', 'Please remove the third-party watermark.')
        ->call('reject', $post->id)
        ->assertHasNoErrors();

    expect($post->refresh()->status)->toBe(MediaPostStatus::Rejected)
        ->and($post->moderation_notes)->toBe('Please remove the third-party watermark.');

    $this->get('/reels')->assertOk()->assertDontSee('third-party watermark');
});

it('keeps the provider library scoped to the signed-in provider', function () {
    [$owner, $provider] = aMediaProvider();
    [, $otherProvider] = aMediaProvider();
    $this->actingAs($owner);

    $post = $provider->mediaPosts()->create([
        'type' => 'video',
        'status' => 'pending_review',
        'visibility' => 'public',
    ]);
    $otherPost = $otherProvider->mediaPosts()->create([
        'type' => 'video',
        'status' => 'pending_review',
        'visibility' => 'public',
    ]);

    expect(fn () => Livewire::test(MediaLibrary::class)->call('deletePost', $otherPost->id))
        ->toThrow(ModelNotFoundException::class);

    expect($post->fresh())->not->toBeNull()
        ->and($otherPost->fresh())->not->toBeNull();
});

it('requires admin access for the moderation queue', function () {
    $this->get('/admin/media')->assertRedirect('/login');

    $this->actingAs(User::factory()->create())
        ->get('/admin/media')
        ->assertForbidden();

    $this->actingAs(aMediaAdmin())
        ->get('/admin/media')
        ->assertOk();
});
