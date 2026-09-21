<?php

namespace Tests\Feature\Filament;

use App\Filament\App\Resources\Products\Pages\CreateProduct;
use App\Filament\App\Resources\Products\Pages\EditProduct;
use App\Filament\App\Resources\Products\Pages\ListProducts;
use App\Filament\App\Resources\Products\ProductResource;
use App\Models\MenuCategory;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Stage2bProductsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function owner_can_create_a_product_with_price_converted_to_minor_units(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id]);
        $image = UploadedFile::fake()->image('product.png', 100, 100);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Cheeseburger',
                'menu_category_id' => $category->id,
                'price_minor' => '12.50',
                'is_available' => true,
                'description' => 'Tasty',
                'image_path' => [$image],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $product = Product::where('name', 'Cheeseburger')->first();

        $this->assertNotNull($product);
        $this->assertSame($restaurant->id, $product->restaurant_id);
        $this->assertSame(1250, $product->price_minor);
        $this->assertTrue($product->is_available);
        $this->assertNotNull($product->image_path);
        $this->assertTrue(Storage::disk('public')->exists($product->image_path));
    }

    #[Test]
    public function owner_can_update_a_product_and_replacing_image_deletes_the_old_file(): void
    {
        Storage::fake('public');

        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $category = MenuCategory::factory()->create(['restaurant_id' => $restaurant->id]);

        Storage::disk('public')->put('products/images/old.png', 'old');
        $product = Product::factory()->forRestaurant($restaurant, $category)->create([
            'price_minor' => 1000,
            'image_path' => 'products/images/old.png',
        ]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        $newImage = UploadedFile::fake()->image('new.png', 80, 80);

        Livewire::test(EditProduct::class, ['record' => $product->id])
            ->fillForm([
                'name' => 'Updated name',
                'menu_category_id' => $category->id,
                'price_minor' => '15.00',
                'is_available' => false,
                'image_path' => [$newImage],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $product->refresh();

        $this->assertSame('Updated name', $product->name);
        $this->assertSame(1500, $product->price_minor);
        $this->assertFalse($product->is_available);
        $this->assertFalse(Storage::disk('public')->exists('products/images/old.png'));
        $this->assertTrue(Storage::disk('public')->exists($product->image_path));
    }

    #[Test]
    public function category_select_only_allows_current_tenants_categories(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;

        $otherOwner = User::factory()->create();
        $otherCategory = MenuCategory::factory()->create(['restaurant_id' => $otherOwner->restaurant->id]);

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(CreateProduct::class)
            ->fillForm([
                'name' => 'Cross tenant attempt',
                'menu_category_id' => $otherCategory->id,
                'price_minor' => '5.00',
            ])
            ->call('create')
            ->assertHasFormErrors(['menu_category_id']);

        $this->assertDatabaseMissing('products', ['name' => 'Cross tenant attempt']);
    }

    #[Test]
    public function owner_can_soft_delete_and_restore_a_product(): void
    {
        $owner = User::factory()->create();
        $restaurant = $owner->restaurant;
        $product = Product::factory()->forRestaurant($restaurant)->create();

        $this->actingAsPanelUser($owner);
        $this->bootAppPanel($restaurant);

        Livewire::test(ListProducts::class)
            ->callTableAction('delete', $product);

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        Livewire::test(ListProducts::class)
            ->filterTable('trashed', 'only')
            ->callTableAction('restore', $product);

        $this->assertDatabaseHas('products', ['id' => $product->id, 'deleted_at' => null]);
    }

    #[Test]
    public function owner_cannot_see_or_mutate_another_tenants_products(): void
    {
        $ownerA = User::factory()->create();
        $ownerB = User::factory()->create();
        $productB = Product::factory()->forRestaurant($ownerB->restaurant)->create();

        $this->actingAsPanelUser($ownerA);
        $this->bootAppPanel($ownerA->restaurant);

        Livewire::test(ListProducts::class)
            ->assertCanNotSeeTableRecords([$productB]);

        $this->actingAsPanelUser($ownerA)
            ->get(ProductResource::getUrl('edit', ['record' => $productB], panel: 'app', tenant: $ownerA->restaurant))
            ->assertNotFound();
    }

    protected function bootAppPanel(Restaurant $restaurant): void
    {
        Filament::setCurrentPanel('app');
        Filament::setTenant($restaurant);
        Filament::bootCurrentPanel();
    }
}
