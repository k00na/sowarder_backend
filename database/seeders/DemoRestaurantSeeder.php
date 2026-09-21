<?php

namespace Database\Seeders;

use App\Enums\Currency;
use App\Enums\PlatformRole;
use App\Enums\RestaurantStatus;
use App\Enums\SessionStatus;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\Restaurant;
use App\Models\TableSession;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoRestaurantSeeder extends Seeder
{
    /**
     * Seed two isolated demo restaurants with small believable menus.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'superadmin@sowarder.test'],
            [
                'name' => 'Platform Super Admin',
                'platform_role' => PlatformRole::SuperAdmin,
                'restaurant_id' => null,
                'password' => Hash::make('password'),
                'cashier_password' => null,
                'email_verified_at' => now(),
            ],
        );

        $this->seedRestaurant([
            'name' => 'Coral Reef Cafe',
            'slug' => 'coral-reef-cafe',
            'currency' => Currency::Mvr,
            'primary_color' => '#0F766E',
            'secondary_color' => '#134E4A',
            'owner_email' => 'owner@coral-reef.test',
            'tables' => ['T1', 'T2', 'T3'],
            'categories' => [
                'Drinks' => [
                    ['name' => 'Iced Lemon Tea', 'price_minor' => 4500],
                    ['name' => 'Fresh Coconut', 'price_minor' => 5000],
                ],
                'Mains' => [
                    ['name' => 'Tuna Salad Bowl', 'price_minor' => 18000],
                    ['name' => 'Chicken Wrap', 'price_minor' => 15000],
                ],
            ],
            'with_open_session_on' => 'T1',
        ]);

        $this->seedRestaurant([
            'name' => 'Harbor Grill',
            'slug' => 'harbor-grill',
            'currency' => Currency::Usd,
            'primary_color' => '#B45309',
            'secondary_color' => '#78350F',
            'owner_email' => 'owner@harbor-grill.test',
            'tables' => ['A1', 'A2'],
            'categories' => [
                'Starters' => [
                    ['name' => 'Fish Cakes', 'price_minor' => 900],
                    ['name' => 'Garden Salad', 'price_minor' => 750],
                ],
                'Grills' => [
                    ['name' => 'Reef Burger', 'price_minor' => 1600],
                    ['name' => 'Grilled Snapper', 'price_minor' => 2200],
                ],
            ],
            'with_open_session_on' => null,
        ]);
    }

    /**
     * @param  array{
     *     name: string,
     *     slug: string,
     *     currency: Currency,
     *     primary_color: string,
     *     secondary_color: string,
     *     owner_email: string,
     *     tables: list<string>,
     *     categories: array<string, list<array{name: string, price_minor: int}>>,
     *     with_open_session_on: ?string
     * }  $config
     */
    private function seedRestaurant(array $config): void
    {
        $restaurant = Restaurant::query()->updateOrCreate(
            ['slug' => $config['slug']],
            [
                'name' => $config['name'],
                'status' => RestaurantStatus::Enabled,
                'currency' => $config['currency'],
                'timezone' => 'Indian/Maldives',
                'primary_color' => $config['primary_color'],
                'secondary_color' => $config['secondary_color'],
                'logo_path' => null,
            ],
        );

        User::query()->updateOrCreate(
            ['email' => $config['owner_email']],
            [
                'name' => $config['name'].' Owner',
                'restaurant_id' => $restaurant->id,
                'platform_role' => PlatformRole::Owner,
                'password' => Hash::make('password'),
                'cashier_password' => Hash::make('cashier-password'),
                'email_verified_at' => now(),
            ],
        );

        $tables = [];
        foreach ($config['tables'] as $label) {
            $tables[$label] = DiningTable::query()->firstOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'label' => $label,
                ],
                [
                    'token' => Str::random(48),
                ],
            );
        }

        $sort = 0;
        $firstProduct = null;
        foreach ($config['categories'] as $categoryName => $products) {
            $category = MenuCategory::query()->updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'name' => $categoryName,
                ],
                [
                    'sort_order' => $sort++,
                ],
            );

            foreach ($products as $productData) {
                $product = Product::query()->updateOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'menu_category_id' => $category->id,
                        'name' => $productData['name'],
                    ],
                    [
                        'description' => null,
                        'price_minor' => $productData['price_minor'],
                        'image_path' => null,
                        'is_available' => true,
                    ],
                );

                $firstProduct ??= $product;
            }
        }

        $openLabel = $config['with_open_session_on'];
        if ($openLabel !== null && isset($tables[$openLabel], $firstProduct)) {
            $table = $tables[$openLabel];

            $session = TableSession::query()->firstOrCreate(
                [
                    'dining_table_id' => $table->id,
                    'status' => SessionStatus::Open,
                ],
                [
                    'restaurant_id' => $restaurant->id,
                    'opened_at' => now()->subMinutes(12),
                ],
            );

            $order = Order::query()->firstOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'idempotency_key' => 'demo-'.$restaurant->slug.'-open-order',
                ],
                [
                    'dining_table_id' => $table->id,
                    'table_session_id' => $session->id,
                    'order_reference' => 'DEMO'.strtoupper(Str::random(4)),
                    'instructions' => 'No onions please',
                    'acknowledged_at' => null,
                    'submitted_at' => now()->subMinutes(10),
                ],
            );

            if ($order->lines()->doesntExist()) {
                OrderLine::query()->create([
                    'order_id' => $order->id,
                    'product_id' => $firstProduct->id,
                    'name' => $firstProduct->name,
                    'unit_price_minor' => $firstProduct->price_minor,
                    'currency' => $restaurant->currency,
                    'quantity' => 2,
                ]);
            }
        }
    }
}
