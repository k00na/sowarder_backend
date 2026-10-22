<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\NeedsAttentionSessions;
use App\Filament\Widgets\PlatformStats;
use App\Filament\Widgets\RestaurantStats;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminRestyleTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function admin_login_uses_the_platform_subheading_without_password_reset(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('Platform admin')
            ->assertSee('Sign in')
            ->assertSee('Remember me')
            ->assertDontSee('Forgot password?');
    }

    #[Test]
    public function owner_login_uses_the_shared_card_without_the_platform_subheading(): void
    {
        $this->get('/app/login')
            ->assertOk()
            ->assertSee('Sign in')
            ->assertSee('Remember me')
            ->assertDontSee('Platform admin')
            ->assertDontSee('Forgot password?');
    }

    #[Test]
    public function platform_dashboard_shows_counts_from_existing_records(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();
        $owned = Restaurant::factory()->create();
        User::factory()->owner($owned)->create();
        Restaurant::factory()->disabled()->create();
        Restaurant::factory()->create();

        $openTable = DiningTable::factory()->create([
            'restaurant_id' => $owned->id,
            'label' => 'Open Table',
        ]);
        $openSession = TableSession::factory()->forTable($openTable)->create();

        $endedTable = DiningTable::factory()->create([
            'restaurant_id' => $owned->id,
            'label' => 'Ended Table',
        ]);
        $endedSession = TableSession::factory()->forTable($endedTable)->ended(true, 2)->create();

        Order::factory()->forSession($openSession)->create([
            'order_reference' => 'UNACKED1',
        ]);
        Order::factory()->forSession($endedSession)->acknowledged()->create([
            'order_reference' => 'ACKED123',
        ]);

        $this->actingAsPanelUser($superAdmin);

        Livewire::test(PlatformStats::class)
            ->assertSee('Restaurants without owners')
            ->assertDontSee('No owner')
            ->assertDontSee('disabled')
            ->assertDontSee('Not invited')
            ->assertDontSee('Open now')
            ->assertDontSee('Not acknowledged');

        Livewire::test(LatestOrders::class)
            ->assertSee('UNACKED1')
            ->assertSee('ACKED123')
            ->assertSee('Not acknowledged')
            ->assertSee('Acknowledged')
            ->assertSee('View all');

        Livewire::test(NeedsAttentionSessions::class)
            ->assertSee('Ended Table')
            ->assertSee('2')
            ->assertDontSee('Open Table');

        Livewire::test(Dashboard::class)
            ->assertSuccessful()
            ->assertSee('New restaurant')
            ->assertSee('Welcome');
    }

    #[Test]
    public function restaurant_tabs_filter_enabled_and_disabled_records(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();
        $enabled = Restaurant::factory()->create(['name' => 'Enabled Cafe']);
        $disabled = Restaurant::factory()->disabled()->create(['name' => 'Disabled Cafe']);

        $this->actingAsPanelUser($superAdmin);

        Livewire::test(RestaurantStats::class)
            ->assertSee('Total')
            ->assertSee('Enabled')
            ->assertSee('Disabled')
            ->assertSee('No owner');

        Livewire::test(ListRestaurants::class)
            ->assertCanSeeTableRecords([$enabled, $disabled])
            ->set('activeTab', 'enabled')
            ->assertCanSeeTableRecords([$enabled])
            ->assertCanNotSeeTableRecords([$disabled])
            ->set('activeTab', 'disabled')
            ->assertCanSeeTableRecords([$disabled])
            ->assertCanNotSeeTableRecords([$enabled]);
    }

    #[Test]
    public function owner_panel_home_keeps_its_navigation(): void
    {
        $owner = User::factory()->create();

        $this->actingAsPanelUser($owner)
            ->get('/app/'.$owner->restaurant->slug)
            ->assertOk()
            ->assertSee('Welcome')
            ->assertSee('Categories')
            ->assertSee('Products')
            ->assertSee('Tables')
            ->assertSee('Sessions')
            ->assertSee('Orders')
            ->assertSee('Cashier preview')
            ->assertSee('Settings')
            ->assertDontSee('New restaurant');
    }
}
