<?php

namespace Tests\Feature\Filament;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashierPreviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function preview_renders_the_live_board_for_the_tenant(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $vacantTable = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'Table 1']);
        $occupiedTable = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'Table 2']);

        $session = TableSession::factory()->forTable($occupiedTable)->create();
        $order = Order::factory()->forSession($session)->create();
        OrderLine::factory()->create([
            'order_id' => $order->id,
            'name' => 'Grilled Fish',
            'unit_price_minor' => 1500,
            'currency' => $restaurant->currency,
            'quantity' => 1,
        ]);

        $this->actingAsPanelUser($owner);

        $response = $this->get(route('filament.app.cashier-preview.show', ['tenant' => $restaurant]));

        $response->assertOk()
            ->assertSee($restaurant->name)
            ->assertSee('Table 1')
            ->assertSee('Table 2')
            ->assertSee('Grilled Fish')
            ->assertSee('occupied unacknowledged');
    }

    #[Test]
    public function preview_is_blocked_for_another_tenant(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $this->actingAsPanelUser($ownerA);

        $this->get(route('filament.app.cashier-preview.show', ['tenant' => $ownerB->restaurant]))
            ->assertNotFound();
    }

    #[Test]
    public function cashier_api_test_panel_is_shown_in_local_environment(): void
    {
        $owner = User::factory()->create();

        $this->actingAsPanelUser($owner);
        $this->app['env'] = 'local';

        $response = $this->get(route('filament.app.cashier-preview.show', ['tenant' => $owner->restaurant]));

        $response->assertOk()
            ->assertSee('API Tests')
            ->assertSee('Cashier API test panel')
            ->assertSee('Test tool — local only')
            ->assertSee($owner->email);
    }

    #[Test]
    public function cashier_api_test_panel_is_hidden_outside_local_and_testing_environments(): void
    {
        $owner = User::factory()->create();

        $this->actingAsPanelUser($owner);
        $this->app['env'] = 'production';

        $response = $this->get(route('filament.app.cashier-preview.show', ['tenant' => $owner->restaurant]));

        $response->assertOk()
            ->assertDontSee('API Tests')
            ->assertDontSee('Cashier API test panel');
    }
}
