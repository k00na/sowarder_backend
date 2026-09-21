<?php

namespace Tests\Feature\Filament;

use App\Enums\RestaurantStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Resources\Orders\Pages\ManageOrders;
use App\Filament\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Resources\TableSessions\Pages\ManageTableSessions;
use App\Filament\Resources\TableSessions\TableSessionResource;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use App\Notifications\OwnerInviteNotification;
use App\Services\OwnerInviteService;
use App\Services\RestaurantAccessService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2aPanelsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function super_admin_can_access_admin_panel_and_owner_cannot(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();
        $owner = User::factory()->create();

        $this->actingAsPanelUser($superAdmin)
            ->get('/admin')
            ->assertOk();

        $this->actingAsPanelUser($owner)
            ->get('/admin')
            ->assertForbidden();
    }

    #[Test]
    public function owner_can_access_enabled_tenant_panel_and_super_admin_cannot(): void
    {
        $owner = User::factory()->create();
        $slug = $owner->restaurant->slug;
        $superAdmin = User::factory()->platformSuperAdmin()->create();

        $this->actingAsPanelUser($owner)
            ->get('/app/'.$slug)
            ->assertOk();

        $this->actingAsPanelUser($superAdmin)
            ->get('/app/'.$slug)
            ->assertForbidden();
    }

    #[Test]
    public function disabled_restaurant_blocks_tenant_panel_and_revokes_cashier_tokens(): void
    {
        $owner = User::factory()->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);

        $token = $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY])->plainTextToken;
        $this->assertDatabaseCount('personal_access_tokens', 1);

        app(RestaurantAccessService::class)->disable($owner->restaurant);

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertFalse($owner->fresh()->canAccessPanel(Filament::getPanel('app')));

        $this->withToken($token)
            ->getJson('/api/v1/cashier/auth/me')
            ->assertUnauthorized();

        $this->actingAsPanelUser($owner->fresh())
            ->get('/app/'.$owner->restaurant->slug)
            ->assertForbidden();
    }

    #[Test]
    public function super_admin_retains_admin_access_when_restaurant_disabled(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();
        $restaurant = Restaurant::factory()->create(['status' => RestaurantStatus::Disabled]);

        $this->actingAsPanelUser($superAdmin)
            ->get('/admin')
            ->assertOk();

        $this->actingAsPanelUser($superAdmin)
            ->get('/admin/restaurants/'.$restaurant->id)
            ->assertOk();
    }

    #[Test]
    public function invite_flow_sets_password_and_allows_tenant_login_page(): void
    {
        Notification::fake();

        $restaurant = Restaurant::factory()->create([
            'slug' => 'invite-cafe',
            'name' => 'Invite Cafe',
        ]);

        $owner = app(OwnerInviteService::class)->invite(
            $restaurant,
            'new-owner@example.com',
            'New Owner',
        );

        Notification::assertSentTo($owner, OwnerInviteNotification::class);

        $url = URL::temporarySignedRoute(
            'owner-invite.show',
            now()->addHour(),
            ['user' => $owner->id],
        );

        $this->get($url)->assertOk()->assertSee('Set your admin password');

        $this->post($url, [
            'password' => 'SecurePass123!',
            'password_confirmation' => 'SecurePass123!',
        ])->assertRedirect('/app/login');

        $this->assertTrue(Hash::check('SecurePass123!', $owner->fresh()->password));

        $this->get('/app/login')->assertOk();
    }

    #[Test]
    public function cannot_invite_email_already_owned_by_another_restaurant(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $restaurant = Restaurant::factory()->create();

        $this->expectException(ValidationException::class);

        app(OwnerInviteService::class)->invite($restaurant, 'taken@example.com', 'Someone');
    }

    #[Test]
    public function diagnostic_resources_are_read_only_for_super_admin(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();
        Order::factory()->create();
        TableSession::factory()->create();

        $this->actingAsPanelUser($superAdmin);

        Livewire::test(ManageOrders::class)->assertSuccessful();
        Livewire::test(ManageTableSessions::class)->assertSuccessful();

        $this->assertFalse(OrderResource::canCreate());
        $this->assertFalse(OrderResource::canEdit(Order::query()->first()));
        $this->assertFalse(TableSessionResource::canCreate());
        $this->assertFalse(TableSessionResource::canEdit(TableSession::query()->first()));
    }

    #[Test]
    public function super_admin_can_create_restaurant_via_filament(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();

        $this->actingAsPanelUser($superAdmin);

        Livewire::test(CreateRestaurant::class)
            ->fillForm([
                'name' => 'Pilot Bistro',
                'slug' => 'pilot-bistro',
                'status' => RestaurantStatus::Enabled->value,
                'currency' => 'MVR',
                'timezone' => 'Indian/Maldives',
                'primary_color' => '#0F766E',
                'secondary_color' => '#134E4A',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('restaurants', [
            'slug' => 'pilot-bistro',
            'name' => 'Pilot Bistro',
        ]);
    }

    #[Test]
    public function disabling_restaurant_via_edit_revokes_cashier_tokens(): void
    {
        $superAdmin = User::factory()->platformSuperAdmin()->create();
        $owner = User::factory()->create([
            'cashier_password' => Hash::make('cashier-password'),
        ]);
        $owner->createToken(User::CASHIER_TOKEN_NAME, [User::CASHIER_TOKEN_ABILITY]);

        $this->actingAsPanelUser($superAdmin);

        Livewire::test(EditRestaurant::class, ['record' => $owner->restaurant->getRouteKey()])
            ->fillForm([
                'status' => RestaurantStatus::Disabled->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseCount('personal_access_tokens', 0);
        $this->assertSame(RestaurantStatus::Disabled, $owner->restaurant->fresh()->status);
    }
}
