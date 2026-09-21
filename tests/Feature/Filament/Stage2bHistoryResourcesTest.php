<?php

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\Orders\OrderResource;
use App\Filament\App\Resources\Orders\Pages\ManageOrders;
use App\Filament\App\Resources\TableSessions\Pages\ManageTableSessions;
use App\Filament\App\Resources\TableSessions\TableSessionResource;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2bHistoryResourcesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_list_and_view_own_table_sessions_but_cannot_mutate(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $session = TableSession::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ManageTableSessions::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$session])
            ->mountTableAction('view', $session)
            ->assertSuccessful();

        $this->assertFalse(TableSessionResource::canCreate());
        $this->assertFalse(TableSessionResource::canEdit($session));
        $this->assertFalse(TableSessionResource::canDelete($session));
    }

    #[Test]
    public function sessions_today_filter_only_shows_sessions_opened_today(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;

        $today = TableSession::factory()->create([
            'restaurant_id' => $restaurant->id,
            'opened_at' => now($restaurant->timezone),
        ]);
        $lastWeek = TableSession::factory()->create([
            'restaurant_id' => $restaurant->id,
            'opened_at' => now($restaurant->timezone)->subWeek(),
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ManageTableSessions::class)
            ->assertCanSeeTableRecords([$today, $lastWeek])
            ->filterTable('today')
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$lastWeek]);
    }

    #[Test]
    public function owner_can_list_and_view_own_orders_with_line_snapshots_but_cannot_mutate(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $order = Order::factory()->create(['restaurant_id' => $restaurant->id]);
        OrderLine::factory()->create([
            'order_id' => $order->id,
            'name' => 'Cheeseburger',
            'unit_price_minor' => 1250,
            'quantity' => 2,
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ManageOrders::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$order])
            ->mountTableAction('view', $order)
            ->assertSuccessful()
            ->assertTableActionDataSet([
                'order_reference' => $order->order_reference,
            ]);

        $this->assertFalse(OrderResource::canCreate());
        $this->assertFalse(OrderResource::canEdit($order));
        $this->assertFalse(OrderResource::canDelete($order));
    }

    #[Test]
    public function owner_cannot_see_another_tenants_sessions_or_orders(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $sessionB = TableSession::factory()->create(['restaurant_id' => $ownerB->restaurant->id]);
        $orderB = Order::factory()->create(['restaurant_id' => $ownerB->restaurant->id]);

        $this->actingAsPanelUser($ownerA);
        $this->bootAppPanel($ownerA->restaurant);

        Livewire::test(ManageTableSessions::class)
            ->assertCanNotSeeTableRecords([$sessionB]);

        Livewire::test(ManageOrders::class)
            ->assertCanNotSeeTableRecords([$orderB]);
    }

    protected function bootAppPanel(Restaurant $restaurant): void
    {
        Filament::setCurrentPanel('app');
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();
    }
}
