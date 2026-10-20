<?php

namespace Tests\Feature\Domain;

use App\Enums\Currency;
use App\Enums\PlatformRole;
use App\Enums\SessionStatus;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Database\Seeders\DemoRestaurantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function demo_seeder_creates_two_isolated_restaurants(): void
    {
        $this->seed(DemoRestaurantSeeder::class);

        $this->assertSame(2, Restaurant::query()->count());
        $this->assertDatabaseHas('restaurants', [
            'slug' => 'coral-reef-cafe',
            'currency' => Currency::Mvr->value,
        ]);
        $this->assertDatabaseHas('restaurants', [
            'slug' => 'harbor-grill',
            'currency' => Currency::Usd->value,
        ]);

        $admin = User::query()->where('email', 'superadmin@sowarder.test')->first();
        $this->assertNotNull($admin);
        $this->assertSame(PlatformRole::SuperAdmin, $admin->platform_role);
        $this->assertNull($admin->restaurant_id);

        $coral = Restaurant::query()->where('slug', 'coral-reef-cafe')->firstOrFail();
        $harbor = Restaurant::query()->where('slug', 'harbor-grill')->firstOrFail();

        $this->assertSame(1, User::query()->where('restaurant_id', $coral->id)->count());
        $this->assertSame(1, User::query()->where('restaurant_id', $harbor->id)->count());

        $this->assertSame(3, DiningTable::query()->where('restaurant_id', $coral->id)->count());
        $this->assertSame(2, DiningTable::query()->where('restaurant_id', $harbor->id)->count());
        $this->assertSame(
            DemoRestaurantSeeder::HARBOR_GRILL_A1_TOKEN,
            DiningTable::query()
                ->where('restaurant_id', $harbor->id)
                ->where('label', DemoRestaurantSeeder::HARBOR_GRILL_A1_LABEL)
                ->value('token'),
        );

        $this->assertSame(4, Product::query()->where('restaurant_id', $coral->id)->count());
        $this->assertSame(29, Product::query()->where('restaurant_id', $harbor->id)->count());
        $this->assertSame(7, $harbor->menuCategories()->count());

        $this->assertSame(0, Product::query()
            ->where('restaurant_id', $coral->id)
            ->whereIn('id', Product::query()->where('restaurant_id', $harbor->id)->pluck('id'))
            ->count());

        $openSessions = TableSession::query()->where('status', SessionStatus::Open)->get();
        $this->assertCount(1, $openSessions);
        $this->assertSame($coral->id, $openSessions->first()->restaurant_id);

        $order = Order::query()->where('restaurant_id', $coral->id)->first();
        $this->assertNotNull($order);
        $this->assertNull($order->acknowledged_at);
        $this->assertTrue($order->lines()->exists());
        $this->assertSame(Currency::Mvr, $order->lines()->first()->currency);
    }

    #[Test]
    public function demo_seeder_is_idempotent_enough_for_local_reset(): void
    {
        $this->seed(DemoRestaurantSeeder::class);
        $this->seed(DemoRestaurantSeeder::class);

        $this->assertSame(2, Restaurant::query()->count());
        $this->assertSame(1, TableSession::query()->where('status', SessionStatus::Open)->count());
        $this->assertSame(1, Order::query()->count());
        $this->assertSame(
            DemoRestaurantSeeder::HARBOR_GRILL_A1_TOKEN,
            DiningTable::query()
                ->where('label', DemoRestaurantSeeder::HARBOR_GRILL_A1_LABEL)
                ->whereHas('restaurant', fn ($query) => $query->where('slug', DemoRestaurantSeeder::HARBOR_GRILL_SLUG))
                ->value('token'),
        );
    }

    #[Test]
    public function demo_harbor_a1_token_command_updates_an_existing_row(): void
    {
        $this->seed(DemoRestaurantSeeder::class);

        $table = DiningTable::query()
            ->where('label', DemoRestaurantSeeder::HARBOR_GRILL_A1_LABEL)
            ->whereHas('restaurant', fn ($query) => $query->where('slug', DemoRestaurantSeeder::HARBOR_GRILL_SLUG))
            ->firstOrFail();

        $table->update(['token' => 'previously-random-harbor-a1-token-00000000']);

        $this->artisan('sowarder:demo-harbor-a1-token')
            ->assertSuccessful();

        $this->assertSame(DemoRestaurantSeeder::HARBOR_GRILL_A1_TOKEN, $table->fresh()->token);
    }
}
