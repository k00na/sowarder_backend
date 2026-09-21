<?php

namespace Tests\Feature\Domain;

use App\Enums\Currency;
use App\Enums\PlatformRole;
use App\Enums\RestaurantStatus;
use App\Enums\SessionStatus;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DomainSchemaTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function factories_create_consistent_tenant_graph(): void
    {
        $restaurant = Restaurant::factory()->currency(Currency::Mvr)->create();
        $owner = User::factory()->owner($restaurant)->create();
        $table = DiningTable::factory()->for($restaurant)->create();
        $category = MenuCategory::factory()->for($restaurant)->create();
        $product = Product::factory()->forRestaurant($restaurant, $category)->create([
            'price_minor' => 1250,
        ]);
        $session = TableSession::factory()->forTable($table)->create();
        $order = Order::factory()->forSession($session)->create([
            'instructions' => 'Extra spicy',
        ]);
        $line = OrderLine::factory()->fromProduct($product)->for($order)->create([
            'quantity' => 2,
        ]);

        $this->assertTrue($owner->isRestaurantOwner());
        $this->assertSame($restaurant->id, $owner->restaurant_id);
        $this->assertSame(PlatformRole::Owner, $owner->platform_role);
        $this->assertTrue(Hash::check('cashier-password', $owner->cashier_password));

        $this->assertSame($restaurant->id, $table->restaurant_id);
        $this->assertGreaterThanOrEqual(32, strlen($table->token));

        $this->assertSame($restaurant->id, $product->restaurant_id);
        $this->assertSame($category->id, $product->menu_category_id);
        $this->assertSame(1250, $product->price_minor);

        $this->assertTrue($session->isOpen());
        $this->assertSame($table->id, $session->dining_table_id);
        $this->assertTrue(Str::isUuid($session->id));

        $this->assertTrue(Str::isUuid($order->id));
        $this->assertNull($order->acknowledged_at);
        $this->assertSame('Extra spicy', $order->instructions);

        $this->assertSame($product->name, $line->name);
        $this->assertSame(1250, $line->unit_price_minor);
        $this->assertSame(Currency::Mvr, $line->currency);
        $this->assertSame(2500, $line->lineTotalMinor());
    }

    #[Test]
    public function platform_super_admin_has_no_restaurant(): void
    {
        $admin = User::factory()->platformSuperAdmin()->create();

        $this->assertTrue($admin->isPlatformSuperAdmin());
        $this->assertNull($admin->restaurant_id);
        $this->assertNull($admin->cashier_password);
    }

    #[Test]
    public function soft_deletes_work_for_menu_and_tables(): void
    {
        $restaurant = Restaurant::factory()->create();
        $table = DiningTable::factory()->for($restaurant)->create();
        $category = MenuCategory::factory()->for($restaurant)->create();
        $product = Product::factory()->forRestaurant($restaurant, $category)->create();

        $table->delete();
        $category->delete();
        $product->delete();

        $this->assertSoftDeleted($table);
        $this->assertSoftDeleted($category);
        $this->assertSoftDeleted($product);
    }

    #[Test]
    public function partial_unique_index_allows_only_one_open_session_per_table(): void
    {
        $index = DB::selectOne("
            SELECT indexname, indexdef
            FROM pg_indexes
            WHERE tablename = 'table_sessions'
              AND indexname = 'table_sessions_one_open_per_dining_table_unique'
        ");

        $this->assertNotNull($index);
        $this->assertStringContainsString('UNIQUE INDEX', strtoupper($index->indexdef));
        $this->assertStringContainsString('dining_table_id', $index->indexdef);
        $this->assertStringContainsString('WHERE', $index->indexdef);
        $this->assertMatchesRegularExpression("/status.*'open'/", $index->indexdef);

        $table = DiningTable::factory()->create();

        TableSession::factory()->forTable($table)->create([
            'status' => SessionStatus::Open,
        ]);

        TableSession::factory()->forTable($table)->ended()->create();

        $this->expectException(QueryException::class);

        TableSession::factory()->forTable($table)->create([
            'status' => SessionStatus::Open,
        ]);
    }

    #[Test]
    public function restaurant_status_and_currency_are_cast(): void
    {
        $restaurant = Restaurant::factory()->disabled()->currency(Currency::Usd)->create();

        $this->assertSame(RestaurantStatus::Disabled, $restaurant->status);
        $this->assertFalse($restaurant->isEnabled());
        $this->assertSame(Currency::Usd, $restaurant->currency);
    }
}
