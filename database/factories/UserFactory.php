<?php

namespace Database\Factories;

use App\Enums\PlatformRole;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    protected static ?string $cashierPassword;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'platform_role' => PlatformRole::Owner,
            'password' => static::$password ??= Hash::make('password'),
            'cashier_password' => static::$cashierPassword ??= Hash::make('cashier-password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function owner(?Restaurant $restaurant = null): static
    {
        return $this->state(fn (array $attributes) => [
            'restaurant_id' => $restaurant?->id ?? Restaurant::factory(),
            'platform_role' => PlatformRole::Owner,
        ]);
    }

    public function platformSuperAdmin(): static
    {
        return $this->state(fn (array $attributes) => [
            'restaurant_id' => null,
            'platform_role' => PlatformRole::SuperAdmin,
            'cashier_password' => null,
        ]);
    }

    public function withoutCashierPassword(): static
    {
        return $this->state(fn (array $attributes) => [
            'cashier_password' => null,
        ]);
    }
}
