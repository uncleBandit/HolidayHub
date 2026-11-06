<?php

namespace Tests\Feature\Auth;

use App\Modules\Identity\Domain\Models\User;
use App\Modules\Identity\Presentation\Livewire\UserProfile;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Passwords are changed on the profile page's Password tab.
 */
class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_can_be_updated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(UserProfile::class)
            ->set('current_password', 'password')
            ->set('password', 'NewPassword1')
            ->set('password_confirmation', 'NewPassword1')
            ->call('savePassword')
            ->assertHasNoErrors()
            ->assertSet('current_password', null)
            ->assertSet('password', null);

        $this->assertTrue(Hash::check('NewPassword1', $user->refresh()->password));
    }

    public function test_correct_password_must_be_provided_to_update_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(UserProfile::class)
            ->set('current_password', 'wrong-password')
            ->set('password', 'NewPassword1')
            ->set('password_confirmation', 'NewPassword1')
            ->call('savePassword')
            ->assertHasErrors(['current_password']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }

    public function test_new_password_must_be_strong_and_confirmed(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)->test(UserProfile::class)
            ->set('current_password', 'password')
            ->set('password', 'weak')
            ->set('password_confirmation', 'different')
            ->call('savePassword')
            ->assertHasErrors(['password']);

        $this->assertTrue(Hash::check('password', $user->refresh()->password));
    }
}
