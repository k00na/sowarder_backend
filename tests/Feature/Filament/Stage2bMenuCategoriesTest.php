<?php

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\MenuCategories\MenuCategoryResource;
use App\Filament\App\Resources\MenuCategories\Pages\CreateMenuCategory;
use App\Filament\App\Resources\MenuCategories\Pages\EditMenuCategory;
use App\Filament\App\Resources\MenuCategories\Pages\ListMenuCategories;
use App\Models\MenuCategory;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2bMenuCategoriesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_create_a_category(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(CreateMenuCategory::class)
            ->fillForm([
                'name' => 'Mains',
                'sort_order' => 5,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('menu_categories', [
            'restaurant_id' => $restaurant->id,
            'name' => 'Mains',
            'sort_order' => 5,
        ]);
    }

    #[Test]
    public function owner_can_update_a_category(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $category = MenuCategory::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Old name',
            'sort_order' => 1,
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(EditMenuCategory::class, ['record' => $category->id])
            ->fillForm([
                'name' => 'New name',
                'sort_order' => 2,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $category->refresh();
        $this->assertSame('New name', $category->name);
        $this->assertSame(2, $category->sort_order);
    }

    #[Test]
    public function owner_can_soft_delete_and_restore_a_category(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $category = MenuCategory::factory()->create([
            'restaurant_id' => $restaurant->id,
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ListMenuCategories::class)
            ->callTableAction('delete', $category);

        $this->assertSoftDeleted('menu_categories', ['id' => $category->id]);

        Livewire::test(ListMenuCategories::class)
            ->filterTable('trashed', 'only')
            ->callTableAction('restore', $category);

        $this->assertDatabaseHas('menu_categories', [
            'id' => $category->id,
            'deleted_at' => null,
        ]);
    }

    #[Test]
    public function list_is_ordered_by_sort_order_then_name(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;

        $second = MenuCategory::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Zebra',
            'sort_order' => 1,
        ]);
        $first = MenuCategory::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Alpha',
            'sort_order' => 0,
        ]);
        $tieBreakLast = MenuCategory::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Zebra',
            'sort_order' => 0,
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ListMenuCategories::class)
            ->assertCanSeeTableRecords([$first, $tieBreakLast, $second], inOrder: true);
    }

    #[Test]
    public function owner_cannot_see_or_mutate_another_tenants_categories(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $categoryB = MenuCategory::factory()->create([
            'restaurant_id' => $ownerB->restaurant->id,
        ]);

        $this->actingAsPanelUser($ownerA);
        $this->bootAppPanel($ownerA->restaurant);

        Livewire::test(ListMenuCategories::class)
            ->assertCanNotSeeTableRecords([$categoryB]);

        $this->actingAsPanelUser($ownerA)
            ->get(MenuCategoryResource::getUrl('edit', ['record' => $categoryB], panel: 'app', tenant: $ownerA->restaurant))
            ->assertNotFound();
    }

    protected function bootAppPanel(Restaurant $restaurant): void
    {
        Filament::setCurrentPanel('app');
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();
    }
}
