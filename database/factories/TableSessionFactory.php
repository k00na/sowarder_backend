<?php

namespace Database\Factories;

use App\Enums\SessionStatus;
use App\Models\DiningTable;
use App\Models\Restaurant;
use App\Models\TableSession;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TableSession>
 */
class TableSessionFactory extends Factory
{
    protected $model = TableSession::class;

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
            'status' => SessionStatus::Open,
            'opened_at' => now(),
            'ended_at' => null,
            'ended_with_unacknowledged' => false,
            'unacknowledged_count_at_end' => null,
        ];
    }

    public function ended(bool $withUnacknowledged = false, int $unacknowledgedCount = 0): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => SessionStatus::Ended,
            'ended_at' => now(),
            'ended_with_unacknowledged' => $withUnacknowledged,
            'unacknowledged_count_at_end' => $withUnacknowledged ? $unacknowledgedCount : 0,
        ]);
    }

    public function forTable(DiningTable $table): static
    {
        return $this->state(fn (array $attributes) => [
            'restaurant_id' => $table->restaurant_id,
            'dining_table_id' => $table->id,
        ]);
    }
}
