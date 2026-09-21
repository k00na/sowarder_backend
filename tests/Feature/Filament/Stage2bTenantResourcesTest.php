<?php

namespace Tests\Feature\Filament;

use App\Enums\Currency;
use App\Filament\App\Pages\ManageRestaurantSettings;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2bTenantResourcesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_update_profile_upload_logo_and_set_cashier_password_without_changing_panel_password(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create([
            'password' => Hash::make('panel-password'),
            'cashier_password' => Hash::make('old-cashier'),
        ]);
        $restaurant = $owner->restaurant;
        $restaurant->update([
            'currency' => Currency::Mvr,
            'name' => 'Old Name',
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        $logo = UploadedFile::fake()->image('logo.png', 100, 100);

        Livewire::test(ManageRestaurantSettings::class)
            ->fillForm([
                'name' => 'Updated Cafe',
                'timezone' => 'Indian/Maldives',
                'currency' => Currency::Usd->value,
                'primary_color' => '#112233',
                'secondary_color' => '#445566',
                'logo_path' => [$logo],
                'cashier_password' => 'new-cashier-pass',
                'cashier_password_confirmation' => 'new-cashier-pass',
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $restaurant->refresh();
        $owner->refresh();

        $this->assertSame('Updated Cafe', $restaurant->name);
        $this->assertSame(Currency::Usd, $restaurant->currency);
        $this->assertSame('#112233', $restaurant->primary_color);
        $this->assertSame('#445566', $restaurant->secondary_color);
        $this->assertNotNull($restaurant->logo_path);
        $this->assertTrue(Storage::disk('public')->exists($restaurant->logo_path));

        $this->assertTrue(Hash::check('panel-password', $owner->password));
        $this->assertTrue(Hash::check('new-cashier-pass', $owner->cashier_password));
        $this->assertFalse(Hash::check('old-cashier', $owner->cashier_password));
    }

    #[Test]
    public function currency_change_is_blocked_while_an_open_session_exists(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $restaurant->update(['currency' => Currency::Mvr]);

        TableSession::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->assertTrue($restaurant->hasOpenTableSessions());

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ManageRestaurantSettings::class)
            ->fillForm([
                'name' => $restaurant->name,
                'timezone' => $restaurant->timezone,
                'currency' => Currency::Usd->value,
                'primary_color' => $restaurant->primary_color,
                'secondary_color' => $restaurant->secondary_color,
            ])
            ->call('save')
            ->assertHasFormErrors(['currency']);

        $this->assertSame(Currency::Mvr, $restaurant->fresh()->currency);
    }

    #[Test]
    public function owner_can_change_currency_when_no_open_sessions_exist(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $restaurant->update(['currency' => Currency::Mvr]);

        TableSession::factory()->ended()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ManageRestaurantSettings::class)
            ->fillForm([
                'name' => $restaurant->name,
                'timezone' => $restaurant->timezone,
                'currency' => Currency::Usd->value,
                'primary_color' => $restaurant->primary_color,
                'secondary_color' => $restaurant->secondary_color,
            ])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertNotified();

        $this->assertSame(Currency::Usd, $restaurant->fresh()->currency);
    }

    #[Test]
    public function owner_cannot_open_another_tenants_settings_url(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();

        $this->actingAsPanelUser($ownerA)
            ->get('/app/'.$ownerB->restaurant->slug.'/profile')
            ->assertNotFound();
    }

    #[Test]
    public function replacing_logo_deletes_the_previous_file(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;

        Storage::disk('public')->put('restaurants/logos/old-logo.png', 'old');
        $restaurant->update(['logo_path' => 'restaurants/logos/old-logo.png']);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        $newLogo = UploadedFile::fake()->image('new-logo.png', 80, 80);

        Livewire::test(ManageRestaurantSettings::class)
            ->fillForm([
                'name' => $restaurant->name,
                'timezone' => $restaurant->timezone,
                'currency' => $restaurant->currency->value,
                'primary_color' => $restaurant->primary_color,
                'secondary_color' => $restaurant->secondary_color,
                'logo_path' => [$newLogo],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $restaurant->refresh();

        $this->assertFalse(Storage::disk('public')->exists('restaurants/logos/old-logo.png'));
        $this->assertNotSame('restaurants/logos/old-logo.png', $restaurant->logo_path);
        $this->assertTrue(Storage::disk('public')->exists($restaurant->logo_path));
    }

    protected function bootAppPanel(Restaurant $restaurant): void
    {
        Filament::setCurrentPanel('app');
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();
    }
}
