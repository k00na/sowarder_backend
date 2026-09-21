<?php

namespace Database\Factories;

use App\Enums\Currency;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderLine>
 */
class OrderLineFactory extends Factory
{
    protected $model = OrderLine::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'product_id' => null,
            'name' => fake()->words(2, true),
            'unit_price_minor' => fake()->numberBetween(500, 15000),
            'currency' => Currency::Mvr,
            'quantity' => fake()->numberBetween(1, 4),
        ];
    }

    public function fromProduct(Product $product, ?Currency $currency = null): static
    {
        return $this->state(fn (array $attributes) => [
            'product_id' => $product->id,
            'name' => $product->name,
            'unit_price_minor' => $product->price_minor,
            'currency' => $currency ?? $product->restaurant->currency,
        ]);
    }
}
