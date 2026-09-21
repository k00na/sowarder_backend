<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company().' Kitchen';

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'logo_path' => null,
            'status' => RestaurantStatus::Enabled,
            'currency' => fake()->randomElement([Currency::Usd, Currency::Mvr]),
            'timezone' => 'Indian/Maldives',
            'primary_color' => '#0F766E',
            'secondary_color' => '#134E4A',
        ];
    }

    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => RestaurantStatus::Disabled,
        ]);
    }

    public function currency(Currency $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => $currency,
        ]);
    }
}
