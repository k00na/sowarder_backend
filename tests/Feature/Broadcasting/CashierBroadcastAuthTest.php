<?php

namespace Tests\Feature\Broadcasting;

use App\Broadcasting\RestaurantChannel;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashierBroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Tests default to the `null` broadcaster, which skips real channel
        // authorization. Switch to the actual `reverb` (Pusher-protocol)
        // connection so `RestaurantChannel::join()` genuinely runs — the
        // auth signature is computed locally, no network call is made.
        // `routes/channels.php` registered its pattern on the boot-time
        // `null` driver instance, so re-register it on the freshly resolved
        // `reverb` driver too.
        config(['broadcasting.default' => 'reverb']);
        Broadcast::channel('restaurant.{restaurantId}', RestaurantChannel::class);
    }

    #[Test]
    public function cashier_can_authorize_onto_own_restaurant_channel(): void
    {
        $restaurant = Restaurant::factory()->create();
        $owner = User::factory()->owner($restaurant)->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);
        $token = $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/cashier/broadcasting/auth', [
                'channel_name' => 'private-restaurant.'.$restaurant->id,
                'socket_id' => '1234.5678',
            ])
            ->assertOk();
    }

    #[Test]
    public function cashier_cannot_authorize_onto_another_restaurants_channel(): void
    {
        $restaurantA = Restaurant::factory()->create();
        $restaurantB = Restaurant::factory()->create();
        $owner = User::factory()->owner($restaurantA)->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);
        $token = $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;

        $this->withToken($token)
            ->postJson('/api/v1/cashier/broadcasting/auth', [
                'channel_name' => 'private-restaurant.'.$restaurantB->id,
                'socket_id' => '1234.5678',
            ])
            ->assertForbidden();
    }

    #[Test]
    public function guest_cannot_authorize_onto_any_restaurant_channel(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->postJson('/api/v1/cashier/broadcasting/auth', [
            'channel_name' => 'private-restaurant.'.$restaurant->id,
            'socket_id' => '1234.5678',
        ])->assertUnauthorized();
    }
}
