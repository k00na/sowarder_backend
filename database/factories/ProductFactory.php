<?php

namespace Database\Factories;

use App\Models\MenuCategory;
use App\Models\Product;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'menu_category_id' => function (array $attributes) {
                return MenuCategory::factory()->create([
                    'restaurant_id' => $attributes['restaurant_id'],
                ])->id;
            },
            'name' => fake()->words(2, true),
            'description' => fake()->optional()->sentence(),
            'price_minor' => fake()->numberBetween(500, 25000),
            'image_path' => null,
            'is_available' => true,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_available' => false,
        ]);
    }

    public function forRestaurant(Restaurant $restaurant, ?MenuCategory $category = null): static
    {
        return $this->state(function (array $attributes) use ($restaurant, $category) {
            $category ??= MenuCategory::factory()->create([
                'restaurant_id' => $restaurant->id,
            ]);

            return [
                'restaurant_id' => $restaurant->id,
                'menu_category_id' => $category->id,
            ];
        });
    }
}
