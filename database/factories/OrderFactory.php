<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'dining_table_id' => function (array $attributes) {
                return DiningTable::factory()->create([
                    'restaurant_id' => $attributes['restaurant_id'],
                ])->id;
            },
            'table_session_id' => function (array $attributes) {
                return TableSession::factory()->create([
                    'restaurant_id' => $attributes['restaurant_id'],
                    'dining_table_id' => $attributes['dining_table_id'],
                    'status' => SessionStatus::Open,
                ])->id;
            },
            'order_reference' => strtoupper(Str::random(8)),
            'idempotency_key' => (string) Str::uuid(),
            'instructions' => fake()->optional()->sentence(),
            'acknowledged_at' => null,
            'submitted_at' => now(),
        ];
    }

    public function acknowledged(): static
    {
        return $this->state(fn (array $attributes) => [
            'acknowledged_at' => now(),
        ]);
    }

    public function forSession(TableSession $session): static
    {
        return $this->state(fn (array $attributes) => [
            'restaurant_id' => $session->restaurant_id,
            'dining_table_id' => $session->dining_table_id,
            'table_session_id' => $session->id,
        ]);
    }
}
