<?php

namespace App\Modules\Identity\Presentation\Livewire;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserProfile extends Component
{
    use WithFileUploads;

    public $tab = 'account'; // Tabs: account, password, profile

    public $user;

    public $name;

    public $email;

    public $current_password;

    public $password;

    public $password_confirmation;

    public $avatar;

    public $profileData = [];

    public function mount()
    {
        $this->user = auth()->user();
        $this->name = $this->user->name;
        $this->email = $this->user->email;

        // Load only safe profile fields
        if ($this->user->profile) {
            $this->profileData = $this->user->profile->only([
                'phone', 'address', 'company_name', 'bio',
            ]);
        }
    }

    protected function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($this->user->id)],
            'password' => ['nullable', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];

        if ($this->user->profile) {
            $rules = array_merge($rules, [
                'profileData.phone' => ['nullable', 'string', 'max:20'],
                'profileData.address' => ['nullable', 'string', 'max:255'],
                'profileData.company_name' => ['nullable', 'string', 'max:255'],
                'profileData.bio' => ['nullable', 'string', 'max:500'],
            ]);
        }

        return $rules;
    }

    public function saveAccount()
    {
        // validateOnly() takes a single field; validate just this form's fields.
        $this->validate(Arr::only($this->rules(), ['name', 'email']));

        $emailChanged = $this->email !== $this->user->email;

        $this->user->fill([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        // A new address has to be proven again, or anyone could move an
        // account to an address they don't own and keep it "verified".
        if ($emailChanged) {
            $this->user->email_verified_at = null;
        }

        $this->user->save();

        if ($emailChanged) {
            $this->user->sendEmailVerificationNotification();
        }

        Log::info("User [{$this->user->id}] updated account info");

        session()->flash('success', 'Account info updated!');
    }

    public function savePassword()
    {
        // The current password is required, so an open session alone is not
        // enough to take over the account.
        $this->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'min:8', 'confirmed', 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'],
        ]);

        $this->user->update([
            'password' => Hash::make($this->password),
        ]);
        $this->reset('current_password', 'password', 'password_confirmation');

        Log::info("User [{$this->user->id}] updated password");
        session()->flash('success', 'Password updated!');
    }

    public function saveProfile()
    {
        $this->validate(Arr::only($this->rules(), ['avatar', 'profileData.phone', 'profileData.address', 'profileData.company_name', 'profileData.bio']));

        if ($this->avatar) {
            // Remove old avatar
            if ($this->user->profile?->avatar && Storage::disk('public')->exists($this->user->profile->avatar)) {
                Storage::disk('public')->delete($this->user->profile->avatar);
            }

            $avatarPath = $this->avatar->store('avatars', 'public');
            $this->profileData['avatar'] = $avatarPath;
        }

        if ($this->user->profile) {
            $this->user->profile()->update($this->profileData);
        }

        Log::info("User [{$this->user->id}] updated profile info");

        session()->flash('success', 'Profile info updated!');
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function render()
    {
        return view('livewire.user-profile');
    }
}
