<?php

namespace Tests\Feature\Filament;

use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2bGuestPreviewTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function preview_shows_branding_and_only_available_products(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'Table 4']);
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id, 'name' => 'Mains']);

        $available = Product::factory()->forRestaurant($restaurant, $category)->create([
            'name' => 'Available Burger',
            'is_available' => true,
        ]);
        $unavailable = Product::factory()->forRestaurant($restaurant, $category)->create([
            'name' => 'Sold Out Pizza',
            'is_available' => false,
        ]);

        $this->actingAsPanelUser($owner);

        $response = $this->get(route('filament.app.guest-preview.tables.show', [
            'tenant' => $restaurant,
            'diningTable' => $table,
        ]));

        $response->assertOk()
            ->assertSee($restaurant->name)
            ->assertSee('Table 4')
            ->assertSee('Mains')
            ->assertSee('Available Burger')
            ->assertDontSee('Sold Out Pizza');
    }

    #[Test]
    public function preview_handles_a_restaurant_with_no_available_products(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAsPanelUser($owner);

        $this->get(route('filament.app.guest-preview.tables.show', [
            'tenant' => $restaurant,
            'diningTable' => $table,
        ]))->assertOk()->assertSee('No available items to show yet.');
    }

    #[Test]
    public function preview_is_blocked_for_another_tenants_table(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $tableB = DiningTable::factory()->create(['restaurant_id' => $ownerB->restaurant->id]);

        $this->actingAsPanelUser($ownerA);

        $this->get(route('filament.app.guest-preview.tables.show', [
            'tenant' => $ownerA->restaurant,
            'diningTable' => $tableB,
        ]))->assertNotFound();
    }

    #[Test]
    public function guest_api_test_panel_is_shown_in_local_environment(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAsPanelUser($owner);
        $this->app['env'] = 'local';

        $response = $this->get(route('filament.app.guest-preview.tables.show', [
            'tenant' => $restaurant,
            'diningTable' => $table,
        ]));

        $response->assertOk()
            ->assertSee('API Tests')
            ->assertSee('Guest API test panel')
            ->assertSee('Test tool — local only')
            ->assertSee($table->token);
    }

    #[Test]
    public function guest_api_test_panel_is_hidden_outside_local_and_testing_environments(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAsPanelUser($owner);
        $this->app['env'] = 'production';

        $response = $this->get(route('filament.app.guest-preview.tables.show', [
            'tenant' => $restaurant,
            'diningTable' => $table,
        ]));

        $response->assertOk()
            ->assertDontSee('API Tests')
            ->assertDontSee('Guest API test panel');
    }
}
