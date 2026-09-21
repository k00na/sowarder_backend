<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\DiningTable;
use App\Models\MenuCategory;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GuestMenuPreviewController extends Controller
{
    /**
     * See `DiningTableQrController` for why `$tenant` must stay untyped and
     * first: it keeps Laravel's positional route-parameter mapping aligned
     * with the `{tenant}/guest-preview/tables/{diningTable}` route.
     */
    public function show(string $tenant, DiningTable $diningTable): View
    {
        $restaurant = Filament::getTenant();

        abort_unless($restaurant !== null && $diningTable->restaurant_id === $restaurant->getKey(), 404);

        $categories = MenuCategory::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereHas('products', fn (Builder $query): Builder => $query->where('is_available', true))
            ->with(['products' => fn (HasMany $query): HasMany => $query
                ->where('is_available', true)
                ->orderBy('name')])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('guest-preview.show', [
            'restaurant' => $restaurant,
            'diningTable' => $diningTable,
            'categories' => $categories,
            'showApiTestPanel' => app()->environment(['local', 'testing']),
        ]);
    }
}
