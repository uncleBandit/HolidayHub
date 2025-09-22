<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class UserProfile extends Component
{
    use WithFileUploads;

    public $tab = 'account'; // Tabs: account, password, profile
    public $user;
    public $name;
    public $email;
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
                'phone', 'address', 'company_name', 'bio'
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
        $this->validateOnly(['name','email']);

        $this->user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        Log::info("User [{$this->user->id}] updated account info");

        session()->flash('success', 'Account info updated!');
    }

    public function savePassword()
    {
        $this->validateOnly(['password','password_confirmation']);

        if ($this->password) {
            $this->user->update([
                'password' => Hash::make($this->password),
            ]);
            Log::info("User [{$this->user->id}] updated password");
            session()->flash('success', 'Password updated!');
        }
    }

    public function saveProfile()
    {
        $this->validateOnly(array_keys($this->profileData));

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
