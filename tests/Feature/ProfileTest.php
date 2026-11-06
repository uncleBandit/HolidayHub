<?php

namespace Tests\Feature;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Presentation\Livewire\UserProfile;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The account screen is the UserProfile page at /userprofile; /profile keeps
 * working as a redirect, and PATCH/DELETE /profile are JSON endpoints.
 */
class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_address_leads_to_the_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile')->assertRedirect('/userprofile');
        $this->actingAs($user)->get('/userprofile')->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(UserProfile::class)
            ->set('name', 'Test User')
            ->set('email', 'test@example.com')
            ->call('saveAccount')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        // A new address must be verified again.
        $this->assertNull($user->email_verified_at);
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(UserProfile::class)
            ->set('name', 'Test User')
            ->call('saveAccount')
            ->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
        Notification::assertNothingSent();
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/profile', ['password' => 'password'])
            ->assertNoContent();

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->deleteJson('/profile', ['password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertNotNull($user->fresh());
    }
}
