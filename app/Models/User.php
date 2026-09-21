<?php

namespace App\Models;

use App\Enums\PlatformRole;
use App\Enums\RestaurantStatus;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'restaurant_id',
    'name',
    'email',
    'platform_role',
    'password',
    'cashier_password',
])]
#[Hidden(['password', 'cashier_password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasTenants
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    public const CASHIER_TOKEN_ABILITY = 'cashier';

    public const CASHIER_TOKEN_NAME = 'cashier-device';

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'platform_role' => 'owner',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'cashier_password' => 'hashed',
            'platform_role' => PlatformRole::class,
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function isPlatformSuperAdmin(): bool
    {
        return $this->platform_role === PlatformRole::SuperAdmin;
    }

    public function isRestaurantOwner(): bool
    {
        return $this->platform_role === PlatformRole::Owner && $this->restaurant_id !== null;
    }

    public function canAccessCashierApi(): bool
    {
        if (! $this->isRestaurantOwner() || blank($this->cashier_password)) {
            return false;
        }

        return $this->restaurant !== null
            && $this->restaurant->status === RestaurantStatus::Enabled;
    }

    public function belongsToRestaurant(int $restaurantId): bool
    {
        return $this->restaurant_id === $restaurantId;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->isPlatformSuperAdmin(),
            'app' => $this->isRestaurantOwner()
                && $this->restaurant !== null
                && $this->restaurant->isEnabled(),
            default => false,
        };
    }

    public function canAccessTenant(Model $tenant): bool
    {
        if (! $tenant instanceof Restaurant) {
            return false;
        }

        return $this->belongsToRestaurant($tenant->id) && $tenant->isEnabled();
    }

    /**
     * @return Collection<int, Restaurant>
     */
    public function getTenants(Panel $panel): Collection
    {
        if ($this->restaurant === null) {
            return collect();
        }

        return collect([$this->restaurant]);
    }
}
