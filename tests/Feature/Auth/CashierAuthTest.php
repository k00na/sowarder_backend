<?php

namespace Tests\Feature\Auth;

use App\Enums\RestaurantStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CashierAuthTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function cashier_can_login_with_cashier_password(): void
    {
        $owner = User::factory()->create([
            'password' => Hash::make('panel-password'),
            'cashier_password' => Hash::make('cashier-password'),
        ]);

        $response = $this->postJson('/api/v1/cashier/auth/login', [
            'email' => $owner->email,
            'cashier_password' => 'cashier-password',
        ]);

        $response->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('restaurant.id', $owner->restaurant_id)
            ->assertJsonStructure(['token', 'token_type', 'restaurant' => ['id', 'name', 'currency', 'timezone']]);

        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    #[Test]
    public function panel_password_cannot_obtain_cashier_token(): void
    {
        $owner = User::factory()->create([
            'password' => Hash::make('panel-password'),
            'cashier_password' => Hash::make('cashier-password'),
        ]);

        $this->postJson('/api/v1/cashier/auth/login', [
            'email' => $owner->email,
            'cashier_password' => 'panel-password',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'validation_failed');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    #[Test]
    public function disabled_restaurant_cannot_login(): void
    {
        $owner = User::factory()->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);
        $owner->restaurant->update(['status' => RestaurantStatus::Disabled]);

        $this->postJson('/api/v1/cashier/auth/login', [
            'email' => $owner->email,
            'cashier_password' => 'cashier-password',
        ])->assertForbidden()
            ->assertJsonPath('error.code', 'restaurant_disabled');
    }

    #[Test]
    public function me_and_logout_work_and_revocation_blocks_me(): void
    {
        $owner = User::factory()->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);

        $login = $this->postJson('/api/v1/cashier/auth/login', [
            'email' => $owner->email,
            'cashier_password' => 'cashier-password',
        ])->assertOk();

        $token = $login->json('token');

        $this->withToken($token)
            ->getJson('/api/v1/cashier/auth/me')
            ->assertOk()
            ->assertJsonPath('user.email', $owner->email)
            ->assertJsonPath('restaurant.id', $owner->restaurant_id);

        $this->withToken($token)
            ->postJson('/api/v1/cashier/auth/logout')
            ->assertNoContent();

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->withToken($token)
            ->getJson('/api/v1/cashier/auth/me')
            ->assertUnauthorized();
    }

    #[Test]
    public function disabled_restaurant_blocks_existing_cashier_token(): void
    {
        $owner = User::factory()->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);

        $token = $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;

        $owner->restaurant->update(['status' => RestaurantStatus::Disabled]);

        $this->withToken($token)
            ->getJson('/api/v1/cashier/auth/me')
            ->assertForbidden()
            ->assertJsonPath('error.code', 'restaurant_disabled');
    }
}
