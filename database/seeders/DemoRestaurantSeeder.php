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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DemoRestaurantSeeder extends Seeder
{
    public const HARBOR_GRILL_SLUG = 'harbor-grill';

    public const HARBOR_GRILL_A1_LABEL = 'A1';

    public const HARBOR_GRILL_A1_TOKEN = 'test';

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
            'table_tokens' => [self::HARBOR_GRILL_A1_LABEL => self::HARBOR_GRILL_A1_TOKEN],
            'categories' => $this->harborGrillMenu(),
            'sync_catalog' => true,
            'with_open_session_on' => null,
        ]);
    }

    /**
     * Point Harbor Grill A1 at the local-demo token `test` (existing DBs).
     */
    public static function assignHarborGrillA1DemoToken(): ?DiningTable
    {
        $table = DiningTable::query()
            ->where('label', self::HARBOR_GRILL_A1_LABEL)
            ->whereHas('restaurant', fn ($query) => $query->where('slug', self::HARBOR_GRILL_SLUG))
            ->first();

        if ($table === null) {
            return null;
        }

        self::forceTableToken($table, self::HARBOR_GRILL_A1_TOKEN);

        return $table->fresh();
    }

    private static function forceTableToken(DiningTable $table, string $token): void
    {
        if ($table->token === $token) {
            return;
        }

        DiningTable::withTrashed()
            ->where('token', $token)
            ->whereKeyNot($table->id)
            ->get()
            ->each(fn (DiningTable $other) => $other->regenerateToken());

        $table->update(['token' => $token]);
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
     *     table_tokens?: array<string, string>,
     *     categories: array<string, list<array{name: string, price_minor: int, description?: string}>>,
     *     sync_catalog?: bool,
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
        $tableTokens = $config['table_tokens'] ?? [];
        foreach ($config['tables'] as $label) {
            $forcedToken = $tableTokens[$label] ?? null;
            $table = DiningTable::query()->firstOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'label' => $label,
                ],
                [
                    'token' => $forcedToken ?? Str::random(48),
                ],
            );

            if ($forcedToken !== null) {
                self::forceTableToken($table, $forcedToken);
            }

            $tables[$label] = $table->fresh() ?? $table;
        }

        $sort = 0;
        $firstProduct = null;
        $productNames = [];
        foreach ($config['categories'] as $categoryName => $products) {
            $category = MenuCategory::query()->withTrashed()->updateOrCreate(
                [
                    'restaurant_id' => $restaurant->id,
                    'name' => $categoryName,
                ],
                [
                    'sort_order' => $sort++,
                ],
            );
            if ($category->trashed()) {
                $category->restore();
            }

            foreach ($products as $productData) {
                $productNames[] = $productData['name'];
                $imageFile = Str::slug($categoryName).'_'.Str::slug($productData['name']).'.jpg';

                $product = Product::query()->withTrashed()->updateOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name' => $productData['name'],
                    ],
                    [
                        'menu_category_id' => $category->id,
                        'description' => $productData['description'] ?? null,
                        'price_minor' => $productData['price_minor'],
                        'image_path' => $this->storeSeedImage($imageFile),
                        'is_available' => true,
                    ],
                );
                if ($product->trashed()) {
                    $product->restore();
                }

                $firstProduct ??= $product;
            }
        }

        if ($config['sync_catalog'] ?? false) {
            Product::query()
                ->where('restaurant_id', $restaurant->id)
                ->whereNotIn('name', $productNames)
                ->forceDelete();

            MenuCategory::query()
                ->where('restaurant_id', $restaurant->id)
                ->whereNotIn('name', array_keys($config['categories']))
                ->forceDelete();
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

    /**
     * @return array<string, list<array{name: string, description: string, price_minor: int}>>
     */
    private function harborGrillMenu(): array
    {
        return [
            'Starters' => [
                ['name' => 'Garlic Bread', 'description' => 'Toasted sourdough with garlic butter and parsley.', 'price_minor' => 650],
                ['name' => 'Crispy Calamari', 'description' => 'Lightly fried squid with lemon and garlic aioli.', 'price_minor' => 1100],
                ['name' => 'Tomato Bruschetta', 'description' => 'Grilled bread topped with ripe tomato, basil, and olive oil.', 'price_minor' => 850],
                ['name' => 'Soup of the Day', 'description' => 'Ask the table for today’s pot. Served with bread.', 'price_minor' => 700],
            ],
            'Pizzas' => [
                ['name' => 'Margherita', 'description' => 'San Marzano tomato, fresh mozzarella, and basil.', 'price_minor' => 1400],
                ['name' => 'Pepperoni', 'description' => 'Tomato sauce, mozzarella, and cupped pepperoni.', 'price_minor' => 1600],
                ['name' => 'BBQ Chicken', 'description' => 'Smoked chicken, red onion, mozzarella, and barbecue sauce.', 'price_minor' => 1750],
                ['name' => 'Mushroom Truffle', 'description' => 'Roasted mushrooms, mozzarella, and truffle oil.', 'price_minor' => 1800],
            ],
            'Grills' => [
                ['name' => 'Reef Burger', 'description' => 'Beef patty, cheddar, lettuce, and house sauce on a brioche bun.', 'price_minor' => 1600],
                ['name' => 'Grilled Snapper', 'description' => 'Charred snapper with lemon and herb butter.', 'price_minor' => 2200],
                ['name' => 'Ribeye Steak', 'description' => '10 oz ribeye with grilled vegetables.', 'price_minor' => 2800],
                ['name' => 'Chicken Skewer', 'description' => 'Marinated chicken, peppers, and warm flatbread.', 'price_minor' => 1500],
            ],
            'Vegetarian' => [
                ['name' => 'Garden Salad', 'description' => 'Mixed leaves, cucumber, tomato, and lemon vinaigrette.', 'price_minor' => 950],
                ['name' => 'Veggie Bowl', 'description' => 'Quinoa, roasted vegetables, chickpeas, and tahini.', 'price_minor' => 1400],
                ['name' => 'Mushroom Risotto', 'description' => 'Arborio rice, mixed mushrooms, and parmesan.', 'price_minor' => 1650],
                ['name' => 'Falafel Plate', 'description' => 'Crispy falafel, salad, and warm pita.', 'price_minor' => 1350],
                ['name' => 'Caprese', 'description' => 'Tomato, mozzarella, and basil with olive oil.', 'price_minor' => 1000],
            ],
            'Sweets' => [
                ['name' => 'Chocolate Brownie', 'description' => 'Warm brownie with vanilla ice cream.', 'price_minor' => 800],
                ['name' => 'Cheesecake', 'description' => 'Baked vanilla cheesecake with berry compote.', 'price_minor' => 850],
                ['name' => 'Tiramisu', 'description' => 'Espresso-soaked sponge and mascarpone.', 'price_minor' => 900],
                ['name' => 'Ice Cream', 'description' => 'Two scoops. Vanilla, chocolate, or strawberry.', 'price_minor' => 450],
            ],
            'Beer' => [
                ['name' => 'Lager', 'description' => 'Crisp pale lager, served cold.', 'price_minor' => 600],
                ['name' => 'IPA', 'description' => 'Hoppy ale with a citrus aroma.', 'price_minor' => 700],
                ['name' => 'Wheat Beer', 'description' => 'Light wheat beer with a soft haze.', 'price_minor' => 650],
                ['name' => 'Stout', 'description' => 'Dark stout with roasted malt.', 'price_minor' => 700],
            ],
            'Juice' => [
                ['name' => 'Orange Juice', 'description' => 'Freshly squeezed oranges.', 'price_minor' => 450],
                ['name' => 'Apple Juice', 'description' => 'Cold-pressed apple.', 'price_minor' => 400],
                ['name' => 'Mango Juice', 'description' => 'Ripe mango, no added sugar.', 'price_minor' => 500],
                ['name' => 'Lemonade', 'description' => 'Lemon, mint, and a little sugar.', 'price_minor' => 450],
            ],
        ];
    }

    private function storeSeedImage(string $filename): ?string
    {
        $source = database_path('seeders/seed_images/'.$filename);
        if (! is_file($source)) {
            return null;
        }

        $path = 'products/images/'.$filename;
        Storage::disk('public')->put($path, (string) file_get_contents($source));

        return $path;
    }
}
