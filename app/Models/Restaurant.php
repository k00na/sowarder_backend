<?php

namespace App\Models;

use App\Enums\Currency;
use App\Enums\PlatformRole;
use App\Enums\RestaurantStatus;
use App\Enums\SessionStatus;
use Database\Factories\RestaurantFactory;
use Filament\Models\Contracts\HasName;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'name',
    'slug',
    'logo_path',
    'status',
    'currency',
    'timezone',
    'primary_color',
    'secondary_color',
])]
class Restaurant extends Model implements HasName
{
    /** @use HasFactory<RestaurantFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'enabled',
        'timezone' => 'Indian/Maldives',
        'primary_color' => '#0F766E',
        'secondary_color' => '#134E4A',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RestaurantStatus::class,
            'currency' => Currency::class,
        ];
    }

    public function isEnabled(): bool
    {
        return $this->status === RestaurantStatus::Enabled;
    }

    public function hasOpenTableSessions(): bool
    {
        return $this->tableSessions()
            ->where('status', SessionStatus::Open)
            ->exists();
    }

    public function getFilamentName(): string
    {
        return $this->name;
    }

    public function owner(): HasOne
    {
        return $this->hasOne(User::class)->where('platform_role', PlatformRole::Owner);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class);
    }

    public function menuCategories(): HasMany
    {
        return $this->hasMany(MenuCategory::class);
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function tableSessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
