<?php

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\DiningTables\DiningTableResource;
use App\Filament\App\Resources\DiningTables\Pages\CreateDiningTable;
use App\Filament\App\Resources\DiningTables\Pages\EditDiningTable;
use App\Filament\App\Resources\DiningTables\Pages\ListDiningTables;
use App\Models\DiningTable;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2bDiningTablesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_create_a_table_and_a_token_is_assigned(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(CreateDiningTable::class)
            ->fillForm(['label' => 'Table 7'])
            ->call('create')
            ->assertHasNoFormErrors();

        $table = DiningTable::where('restaurant_id', $restaurant->id)->where('label', 'Table 7')->first();

        $this->assertNotNull($table);
        $this->assertNotEmpty($table->token);
    }

    #[Test]
    public function regenerate_token_changes_the_token_but_rename_does_not(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id, 'label' => 'Table 1']);
        $originalToken = $table->token;

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(EditDiningTable::class, ['record' => $table->id])
            ->fillForm(['label' => 'Table One'])
            ->call('save')
            ->assertHasNoFormErrors();

        $table->refresh();
        $this->assertSame('Table One', $table->label);
        $this->assertSame($originalToken, $table->token);

        Livewire::test(ListDiningTables::class)
            ->callTableAction('regenerateToken', $table);

        $table->refresh();
        $this->assertNotSame($originalToken, $table->token);
    }

    #[Test]
    public function delete_is_blocked_while_table_has_an_open_session(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        TableSession::factory()->forTable($table)->create();

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ListDiningTables::class)
            ->callTableAction('delete', $table)
            ->assertNotified();

        $this->assertDatabaseHas('dining_tables', ['id' => $table->id, 'deleted_at' => null]);
    }

    #[Test]
    public function delete_is_allowed_once_the_session_has_ended(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);
        TableSession::factory()->forTable($table)->ended()->create();

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ListDiningTables::class)
            ->callTableAction('delete', $table);

        $this->assertSoftDeleted('dining_tables', ['id' => $table->id]);
    }

    #[Test]
    public function qr_downloads_return_correct_content_types(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $table = DiningTable::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAsPanelUser($owner);

        $this->get(route('filament.app.dining-tables.qr.png', ['tenant' => $restaurant, 'diningTable' => $table]))
            ->assertOk()
            ->assertHeader('content-type', 'image/png');

        $this->get(route('filament.app.dining-tables.qr.pdf', ['tenant' => $restaurant, 'diningTable' => $table]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    #[Test]
    public function qr_downloads_are_blocked_for_another_tenants_table(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $tableB = DiningTable::factory()->create(['restaurant_id' => $ownerB->restaurant->id]);

        $this->actingAsPanelUser($ownerA);

        $this->get(route('filament.app.dining-tables.qr.png', ['tenant' => $ownerA->restaurant, 'diningTable' => $tableB]))
            ->assertNotFound();
    }

    #[Test]
    public function owner_cannot_see_or_mutate_another_tenants_tables(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $tableB = DiningTable::factory()->create(['restaurant_id' => $ownerB->restaurant->id]);

        $this->actingAsPanelUser($ownerA);
        $this->bootAppPanel($ownerA->restaurant);

        Livewire::test(ListDiningTables::class)
            ->assertCanNotSeeTableRecords([$tableB]);

        $this->actingAsPanelUser($ownerA)
            ->get(DiningTableResource::getUrl('edit', ['record' => $tableB], panel: 'app', tenant: $ownerA->restaurant))
            ->assertNotFound();
    }

    protected function bootAppPanel(Restaurant $restaurant): void
    {
        Filament::setCurrentPanel('app');
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();
    }
}
