<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Services\CashierBoardService;
use Filament\Facades\Filament;
use Illuminate\Contracts\View\View;

class CashierPreviewController extends Controller
{
    public function __construct(
        private readonly CashierBoardService $board,
    ) {}

    /**
     * See `DiningTableQrController` for why `$tenant` must stay untyped and
     * first: it keeps Laravel's positional route-parameter mapping aligned
     * with the `{tenant}/cashier-preview` route, even though this route has
     * no further path parameters of its own.
     */
    public function show(string $tenant): View
    {
        $restaurant = Filament::getTenant();

        abort_if($restaurant === null, 404);

        $restaurant->loadMissing('owner');

        return view('cashier-preview.show', [
            'restaurant' => $restaurant,
            'cards' => $this->board->board($restaurant, 'current', 'all'),
            'showApiTestPanel' => app()->environment(['local', 'testing']),
        ]);
    }
}
