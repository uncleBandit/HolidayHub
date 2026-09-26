<?php

namespace App\Modules\Identity\Domain\Models;

use App\Modules\Agents\Domain\Models\Agent;
use App\Modules\Providers\Domain\Models\Provider;
use App\Modules\Wishlist\Domain\Models\Wishlist;
// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\App\Modules\Identity\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory,HasRoles,Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }

    public function agent(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function guest(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Guest::class);
    }

    public function provider(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Provider::class);
    }

    public function profile(): MorphTo
    {
        return $this->morphTo();
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    /**
     * Whether this user is platform staff rather than a marketplace participant.
     *
     * Platform staff administer the marketplace; everyone else is a guest,
     * agent or provider whose activity is administered.
     */
    public function isPlatformAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Gate for the Filament admin panel.
     *
     * Filament calls this when resolving an authenticated user for the panel;
     * returning false redirects them out rather than rendering a panel they
     * cannot use. Authorization for individual resources is handled separately
     * by each resource's policy.
     */
    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->isPlatformAdmin();
    }
}
