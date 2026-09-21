<?php

namespace Tests\Feature\Tenancy;

use App\Broadcasting\RestaurantChannel;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function policies_deny_cross_restaurant_access(): void
    {
        $alpha = Restaurant::factory()->create();
        $beta = Restaurant::factory()->create();

        $ownerAlpha = User::factory()->owner($alpha)->create();
        $productBeta = Product::factory()->forRestaurant($beta)->create();

        $this->assertTrue($ownerAlpha->can('view', $alpha));
        $this->assertFalse($ownerAlpha->can('view', $beta));
        $this->assertFalse($ownerAlpha->can('view', $productBeta));
        $this->assertFalse($ownerAlpha->can('update', $productBeta));
    }

    #[Test]
    public function super_admin_can_view_any_restaurant_resources(): void
    {
        $admin = User::factory()->platformSuperAdmin()->create();
        $product = Product::factory()->create();

        $this->assertTrue($admin->can('view', $product->restaurant));
        $this->assertTrue($admin->can('view', $product));
        $this->assertFalse($admin->can('update', $product));
    }

    #[Test]
    public function cashier_route_model_binding_hides_other_restaurant_sessions(): void
    {
        $alpha = Restaurant::factory()->create();
        $beta = Restaurant::factory()->create();

        $ownerAlpha = User::factory()->owner($alpha)->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);
        $sessionBeta = TableSession::factory()->create(['restaurant_id' => $beta->id]);

        $token = $ownerAlpha->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/cashier/sessions/'.$sessionBeta->id)
            ->assertNotFound();
    }

    #[Test]
    public function cashier_can_view_own_restaurant_session(): void
    {
        $owner = User::factory()->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);
        $session = TableSession::factory()->create(['restaurant_id' => $owner->restaurant_id]);

        $token = $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;

        $this->withToken($token)
            ->getJson('/api/v1/cashier/sessions/'.$session->id)
            ->assertOk()
            ->assertJsonPath('session_id', $session->id);
    }

    #[Test]
    public function broadcast_channel_denies_cross_tenant_cashier(): void
    {
        $alpha = Restaurant::factory()->create();
        $beta = Restaurant::factory()->create();
        $ownerAlpha = User::factory()->owner($alpha)->create();

        $token = $ownerAlpha->createToken('cashier-device', [User::CASHIER_TOKEN_ABILITY]);
        $ownerAlpha->withAccessToken($token->accessToken);

        $channel = new RestaurantChannel;

        $this->assertTrue($channel->join($ownerAlpha, $alpha->id));
        $this->assertFalse($channel->join($ownerAlpha, $beta->id));
    }
}
