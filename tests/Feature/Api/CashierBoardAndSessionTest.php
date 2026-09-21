<?php

namespace Tests\Feature\Api;

use App\Enums\SessionStatus;
use App\Events\OrderAcknowledged;
use App\Events\SessionEnded;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashierBoardAndSessionTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function board_derives_states_and_supports_occupancy_filters(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $vacant = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'T1']);
        $unacked = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'T2']);
        $acked = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'T3']);

        $unackedSession = TableSession::factory()->forTable($unacked)->create();
        $this->createOrderWithLine($unackedSession, 'Latte', 500, 2);

        $ackedSession = TableSession::factory()->forTable($acked)->create();
        $order = $this->createOrderWithLine($ackedSession, 'Croissant', 300, 1);
        $order->update(['acknowledged_at' => now()]);

        $response = $this->withToken($token)
            ->getJson('/api/v1/cashier/board?scope=current&occupancy=all')
            ->assertOk();

        $response->assertJsonPath('currency', $restaurant->currency->value)
            ->assertJsonCount(3, 'cards');

        $cardsByLabel = collect($response->json('cards'))->keyBy('table_label');
        $this->assertSame('vacant', $cardsByLabel['T1']['state']);
        $this->assertNull($cardsByLabel['T1']['session_id']);
        $this->assertSame('occupied_unacknowledged', $cardsByLabel['T2']['state']);
        $this->assertTrue($cardsByLabel['T2']['has_unacknowledged_orders']);
        $this->assertSame(1000, $cardsByLabel['T2']['session_total_minor']);
        $this->assertSame(['Latte'], $cardsByLabel['T2']['item_preview']);
        $this->assertSame('occupied_acknowledged', $cardsByLabel['T3']['state']);
        $this->assertFalse($cardsByLabel['T3']['has_unacknowledged_orders']);

        $this->withToken($token)
            ->getJson('/api/v1/cashier/board?scope=current&occupancy=occupied')
            ->assertOk()
            ->assertJsonCount(2, 'cards');

        $this->withToken($token)
            ->getJson('/api/v1/cashier/board?scope=current&occupancy=vacant')
            ->assertOk()
            ->assertJsonCount(1, 'cards')
            ->assertJsonPath('cards.0.table_label', 'T1');
    }

    #[Test]
    public function today_scope_backfills_vacant_tables_from_their_most_recent_ended_session(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'T1']);
        $endedSession = TableSession::factory()->forTable($table)->ended()->create();
        $order = $this->createOrderWithLine($endedSession, 'Espresso', 400, 1);
        $order->update(['acknowledged_at' => now()]);

        $current = $this->withToken($token)
            ->getJson('/api/v1/cashier/board?scope=current&occupancy=all')
            ->assertOk();
        $this->assertSame('vacant', $current->json('cards.0.state'));
        $this->assertNull($current->json('cards.0.session_id'));

        $today = $this->withToken($token)
            ->getJson('/api/v1/cashier/board?scope=today&occupancy=all')
            ->assertOk();
        $this->assertSame('vacant', $today->json('cards.0.state'));
        $this->assertSame($endedSession->id, $today->json('cards.0.session_id'));
        $this->assertSame(400, $today->json('cards.0.session_total_minor'));
    }

    #[Test]
    public function session_detail_returns_orders_grouped_with_line_snapshots(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->create();
        $this->createOrderWithLine($session, 'Burger', 1200, 1);

        $this->withToken($token)
            ->getJson('/api/v1/cashier/sessions/'.$session->id)
            ->assertOk()
            ->assertJsonPath('session_id', $session->id)
            ->assertJsonPath('status', 'open')
            ->assertJsonPath('session_total_minor', 1200)
            ->assertJsonPath('has_unacknowledged_orders', true)
            ->assertJsonCount(1, 'orders')
            ->assertJsonPath('orders.0.lines.0.product_name', 'Burger')
            ->assertJsonPath('orders.0.lines.0.line_total_minor', 1200);
    }

    #[Test]
    public function accept_order_acknowledges_all_unacknowledged_orders(): void
    {
        Event::fake([OrderAcknowledged::class]);

        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->create();
        $orderOne = $this->createOrderWithLine($session, 'Tea', 200, 1);
        $orderTwo = $this->createOrderWithLine($session, 'Cake', 600, 1);

        $this->withToken($token)
            ->postJson('/api/v1/cashier/sessions/'.$session->id.'/accept')
            ->assertOk()
            ->assertJsonPath('has_unacknowledged_orders', false);

        $this->assertNotNull($orderOne->refresh()->acknowledged_at);
        $this->assertNotNull($orderTwo->refresh()->acknowledged_at);

        Event::assertDispatched(OrderAcknowledged::class);
    }

    #[Test]
    public function accept_order_on_already_ended_session_returns_conflict(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->ended()->create();

        $this->withToken($token)
            ->postJson('/api/v1/cashier/sessions/'.$session->id.'/accept')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');
    }

    #[Test]
    public function end_session_without_confirm_rejects_when_unacknowledged_orders_remain(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->create();
        $this->createOrderWithLine($session, 'Soup', 800, 1);

        $this->withToken($token)
            ->postJson('/api/v1/cashier/sessions/'.$session->id.'/end')
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'conflict');

        $this->assertSame(SessionStatus::Open, $session->refresh()->status);
    }

    #[Test]
    public function end_session_with_confirm_ends_and_records_unacknowledged_signal(): void
    {
        Event::fake([SessionEnded::class]);

        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->create();
        $this->createOrderWithLine($session, 'Soup', 800, 1);

        $this->withToken($token)
            ->postJson('/api/v1/cashier/sessions/'.$session->id.'/end', ['confirm_unacknowledged' => true])
            ->assertOk()
            ->assertJsonPath('status', 'ended');

        $session->refresh();
        $this->assertSame(SessionStatus::Ended, $session->status);
        $this->assertTrue($session->ended_with_unacknowledged);
        $this->assertSame(1, $session->unacknowledged_count_at_end);

        Event::assertDispatched(SessionEnded::class);
    }

    #[Test]
    public function end_session_with_no_unacknowledged_orders_does_not_require_confirm(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->create();
        $order = $this->createOrderWithLine($session, 'Soup', 800, 1);
        $order->update(['acknowledged_at' => now()]);

        $this->withToken($token)
            ->postJson('/api/v1/cashier/sessions/'.$session->id.'/end')
            ->assertOk()
            ->assertJsonPath('status', 'ended');

        $this->assertFalse($session->refresh()->ended_with_unacknowledged);
    }

    #[Test]
    public function ending_an_already_ended_session_is_idempotent(): void
    {
        $restaurant = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurant);

        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        $session = TableSession::factory()->forTable($table)->ended()->create();
        $endedAt = $session->ended_at;

        $this->withToken($token)
            ->postJson('/api/v1/cashier/sessions/'.$session->id.'/end')
            ->assertOk()
            ->assertJsonPath('status', 'ended');

        $this->assertEquals($endedAt, $session->refresh()->ended_at);
    }

    #[Test]
    public function cross_tenant_session_access_is_hidden(): void
    {
        $restaurantA = Restaurant::factory()->create();
        $restaurantB = Restaurant::factory()->create();
        [, $token] = $this->cashierTokenFor($restaurantA);

        $tableB = DiningTable::factory()->create(['restaurant_id' => $restaurantB->id]);
        $sessionB = TableSession::factory()->forTable($tableB)->create();

        $this->withToken($token)
            ->getJson('/api/v1/cashier/sessions/'.$sessionB->id)
            ->assertNotFound();
    }

    #[Test]
    public function invalid_or_revoked_token_is_rejected(): void
    {
        $this->withToken('not-a-real-token')
            ->getJson('/api/v1/cashier/board')
            ->assertUnauthorized();
    }

    /**
     * @return array{0: User, 1: string}
     */
    private function cashierTokenFor(Restaurant $restaurant): array
    {
        $owner = User::factory()->owner($restaurant)->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);

        $token = $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;

        return [$owner, $token];
    }

    private function createOrderWithLine(TableSession $session, string $name, int $unitPriceMinor, int $quantity): Order
    {
        $order = Order::factory()->forSession($session)->create();

        OrderLine::factory()->create([
            'order_id' => $order->id,
            'name' => $name,
            'unit_price_minor' => $unitPriceMinor,
            'currency' => $session->restaurant->currency,
            'quantity' => $quantity,
        ]);

        return $order;
    }
}
