<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Http\Controllers\Controller;
use App\Models\MenuCategory;
use App\Models\Product;
use App\Services\DiningTableTokenResolver;
use App\Services\TableSessionResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;

class GuestMenuController extends Controller
{
    public function __construct(
        private readonly DiningTableTokenResolver $tokenResolver,
        private readonly TableSessionResolver $sessionResolver,
    ) {}

    public function show(string $tableToken): JsonResponse
    {
        $table = $this->tokenResolver->resolve($tableToken);
        $restaurant = $table->restaurant;

        $currentSession = $this->sessionResolver->currentOpen($table);

        $categories = MenuCategory::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereHas('products', fn (Builder $query): Builder => $query->where('is_available', true))
            ->with(['products' => fn (HasMany $query): HasMany => $query
                ->where('is_available', true)
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return response()->json([
            'restaurant' => [
                'name' => $restaurant->name,
                'currency' => $restaurant->currency->value,
                'logo_url' => $restaurant->logo_path !== null
                    ? Storage::disk('public')->url($restaurant->logo_path)
                    : null,
                'primary_color' => $restaurant->primary_color,
                'secondary_color' => $restaurant->secondary_color,
            ],
            'table' => [
                'label' => $table->label,
            ],
            'current_session_id' => $currentSession?->id,
            'categories' => $categories->map(fn (MenuCategory $category): array => [
                'id' => (string) $category->id,
                'name' => $category->name,
                'sort_order' => $category->sort_order,
                'products' => $category->products->map(fn (Product $product): array => [
                    'id' => (string) $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'unit_price_minor' => $product->price_minor,
                    'currency' => $restaurant->currency->value,
                    'image_url' => $product->image_path !== null
                        ? Storage::disk('public')->url($product->image_path)
                        : null,
                    'available' => $product->is_available,
                ])->all(),
            ])->all(),
        ])->header('Cache-Control', 'private, max-age=10');
    }
}
