<?php

namespace Tests\Feature\Api;

use App\Enums\RestaurantStatus;
use App\Enums\SessionStatus;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TableSession;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GuestMenuAndOrderTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function menu_returns_relative_public_disk_urls_on_localhost(): void
    {
        $restaurant = Restaurant::factory()->create([
            'name' => 'Test Diner',
            'logo_path' => 'restaurants/logos/diner.png',
        ]);
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'T1']);
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        Product::factory()->forRestaurant($restaurant, $category)->create([
            'is_available' => true,
            'image_path' => 'products/images/item.jpg',
        ]);

        $this->assertSame('/storage', config('filesystems.disks.public.url'));

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertOk()
            ->assertJsonPath('restaurant.logo_url', '/storage/restaurants/logos/diner.png')
            ->assertJsonPath('categories.0.products.0.image_url', '/storage/products/images/item.jpg');
    }

    #[Test]
    public function menu_returns_absolute_public_disk_urls_when_configured(): void
    {
        config(['filesystems.disks.public.url' => 'https://cdn.example.com/media']);
        Storage::forgetDisk('public');

        $restaurant = Restaurant::factory()->create([
            'logo_path' => 'restaurants/logos/diner.png',
        ]);
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        Product::factory()->forRestaurant($restaurant, $category)->create([
            'is_available' => true,
            'image_path' => 'products/images/item.jpg',
        ]);

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertOk()
            ->assertJsonPath('restaurant.logo_url', 'https://cdn.example.com/media/restaurants/logos/diner.png')
            ->assertJsonPath('categories.0.products.0.image_url', 'https://cdn.example.com/media/products/images/item.jpg');
    }

    #[Test]
    public function menu_resolves_a_short_demo_token(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Harbor Grill']);
        $table = DiningTable::factory()->create([
            'restaurant_id' => $restaurant->id,
            'label' => 'A1',
            'token' => 'test',
        ]);

        $this->assertSame('test', $table->token);

        $this->getJson('/api/v1/guest/tables/test/menu')
            ->assertOk()
            ->assertJsonPath('restaurant.name', 'Harbor Grill')
            ->assertJsonPath('table.label', 'A1');
    }

    #[Test]
    public function menu_returns_branding_and_available_products_only(): void
    {
        $restaurant = Restaurant::factory()->create(['name' => 'Test Diner']);
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'T1']);
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id, 'sort_order' => 1]);
        $available = Product::factory()->forRestaurant($restaurant, $category)->create(['is_available' => true, 'name' => 'Available Item']);
        Product::factory()->forRestaurant($restaurant, $category)->create(['is_available' => false, 'name' => 'Hidden Item']);

        $response = $this->getJson("/api/v1/guest/tables/{$table->token}/menu");

        $response->assertOk()
            ->assertJsonPath('restaurant.name', 'Test Diner')
            ->assertJsonPath('restaurant.currency', $restaurant->currency->value)
            ->assertJsonPath('table.label', 'T1')
            ->assertJsonPath('current_session_id', null)
            ->assertJsonCount(1, 'categories')
            ->assertJsonCount(1, 'categories.0.products')
            ->assertJsonPath('categories.0.products.0.id', (string) $available->id)
            ->assertJsonPath('categories.0.products.0.available', true);
    }

    #[Test]
    public function menu_excludes_categories_with_no_available_products(): void
    {
        $restaurant = Restaurant::factory()->create();
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        Product::factory()->forRestaurant($restaurant, $category)->create(['is_available' => false]);

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertOk()
            ->assertJsonCount(0, 'categories');
    }

    #[Test]
    public function menu_rejects_invalid_token(): void
    {
        $this->getJson('/api/v1/guest/tables/does-not-exist-00000000000000000000/menu')
            ->assertNotFound()
            ->assertJsonPath('error.code', 'not_found');
    }

    #[Test]
    public function menu_rejects_disabled_restaurant(): void
    {
        $restaurant = Restaurant::factory()->disabled()->create();
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertForbidden()
            ->assertJsonPath('error.code', 'restaurant_disabled');
    }

    #[Test]
    public function menu_browsing_does_not_create_a_session_but_reflects_an_existing_open_one(): void
    {
        $table = DiningTable::factory()->create();

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertOk()
            ->assertJsonPath('current_session_id', null);

        $this->assertDatabaseCount('table_sessions', 0);

        $session = TableSession::factory()->forTable($table)->create();

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertOk()
            ->assertJsonPath('current_session_id', $session->id);
    }

    #[Test]
    public function order_happy_path_creates_session_and_snapshots_lines(): void
    {
        $restaurant = Restaurant::factory()->create();
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        $productA = Product::factory()->forRestaurant($restaurant, $category)->create(['price_minor' => 1000]);
        $productB = Product::factory()->forRestaurant($restaurant, $category)->create(['price_minor' => 500]);

        $response = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => 'No onions please',
            'lines' => [
                ['product_id' => $productA->id, 'quantity' => 2],
                ['product_id' => $productB->id, 'quantity' => 1],
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('currency', $restaurant->currency->value)
            ->assertJsonPath('total_minor', 2500)
            ->assertJsonStructure(['order_reference', 'session_id', 'currency', 'total_minor', 'acknowledgement']);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_lines', 2);
        $this->assertDatabaseCount('table_sessions', 1);

        $order = Order::query()->first();
        $this->assertSame('No onions please', $order->instructions);
        $this->assertSame($response->json('session_id'), $order->table_session_id);
        $this->assertSame(SessionStatus::Open, $order->tableSession->status);
    }

    #[Test]
    public function idempotent_replay_with_same_body_returns_original_order(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create(['price_minor' => 1200]);
        $idempotencyKey = (string) Str::uuid();

        $payload = [
            'idempotency_key' => $idempotencyKey,
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 1],
            ],
        ];

        $first = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", $payload)->assertCreated();
        $second = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", $payload)->assertCreated();

        $this->assertSame($first->json('order_reference'), $second->json('order_reference'));
        $this->assertDatabaseCount('orders', 1);
    }

    #[Test]
    public function same_idempotency_key_with_different_body_returns_conflict(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create();
        $idempotencyKey = (string) Str::uuid();

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => $idempotencyKey,
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => $idempotencyKey,
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 2]],
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'idempotency_conflict');

        $this->assertDatabaseCount('orders', 1);
    }

    #[Test]
    public function null_expected_session_id_joins_existing_open_session(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create();

        $first = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $second = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertSame($first->json('session_id'), $second->json('session_id'));
        $this->assertDatabaseCount('table_sessions', 1);
        $this->assertDatabaseCount('orders', 2);
    }

    #[Test]
    public function stale_expected_session_id_returns_session_changed(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create();
        TableSession::factory()->forTable($table)->create();

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => (string) Str::uuid(),
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'session_changed');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function price_and_availability_changes_between_menu_load_and_submit_are_reflected_in_the_snapshot(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create(['price_minor' => 1000]);

        // Guest "loaded" the menu at the old price, then the owner reprices before submit.
        $product->update(['price_minor' => 1500]);

        $response = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertCreated();

        $this->assertSame(1500, $response->json('total_minor'));
        $this->assertDatabaseHas('order_lines', ['product_id' => $product->id, 'unit_price_minor' => 1500]);
    }

    #[Test]
    public function unavailable_product_in_cart_is_rejected(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->unavailable()->create();

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function cross_restaurant_product_in_cart_is_rejected(): void
    {
        $table = DiningTable::factory()->create();
        $otherRestaurantProduct = Product::factory()->create();

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $otherRestaurantProduct->id, 'quantity' => 1]],
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function duplicate_product_in_a_single_order_is_rejected(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create();

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [
                ['product_id' => $product->id, 'quantity' => 1],
                ['product_id' => $product->id, 'quantity' => 2],
            ],
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseCount('orders', 0);
    }

    #[Test]
    public function disabled_restaurant_rejects_order_submission(): void
    {
        $restaurant = Restaurant::factory()->create();
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $product = Product::factory()->forRestaurant($restaurant)->create();
        $restaurant->update(['status' => RestaurantStatus::Disabled]);

        $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'restaurant_disabled');
    }

    #[Test]
    public function totals_are_a_pure_sum_of_line_snapshots(): void
    {
        $table = DiningTable::factory()->create();
        $productA = Product::factory()->forRestaurant($table->restaurant)->create(['price_minor' => 999]);
        $productB = Product::factory()->forRestaurant($table->restaurant)->create(['price_minor' => 1]);

        $response = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [
                ['product_id' => $productA->id, 'quantity' => 3],
                ['product_id' => $productB->id, 'quantity' => 7],
            ],
        ])->assertCreated();

        $this->assertSame(999 * 3 + 1 * 7, $response->json('total_minor'));
    }

    #[Test]
    public function order_submission_is_rate_limited_per_table_token(): void
    {
        $table = DiningTable::factory()->create();
        $product = Product::factory()->forRestaurant($table->restaurant)->create();

        for ($i = 0; $i < 10; $i++) {
            $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
                'idempotency_key' => (string) Str::uuid(),
                'expected_session_id' => null,
                'special_instructions' => null,
                'lines' => [['product_id' => $product->id, 'quantity' => 1]],
            ])->assertCreated();
        }

        $response = $this->postJson("/api/v1/guest/tables/{$table->token}/orders", [
            'idempotency_key' => (string) Str::uuid(),
            'expected_session_id' => null,
            'special_instructions' => null,
            'lines' => [['product_id' => $product->id, 'quantity' => 1]],
        ]);

        $response->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limited')
            ->assertHeader('Retry-After');
    }

    #[Test]
    public function menu_reads_are_rate_limited_per_ip(): void
    {
        $table = DiningTable::factory()->create();

        for ($i = 0; $i < 60; $i++) {
            $this->getJson("/api/v1/guest/tables/{$table->token}/menu")->assertOk();
        }

        $this->getJson("/api/v1/guest/tables/{$table->token}/menu")
            ->assertStatus(429)
            ->assertJsonPath('error.code', 'rate_limited')
            ->assertHeader('Retry-After');
    }
}
