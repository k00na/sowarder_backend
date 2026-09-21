<?php

namespace App\Http\Controllers\Api\V1\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cashier\BoardIndexRequest;
use App\Models\User;
use App\Services\CashierBoardService;
use Illuminate\Http\JsonResponse;

class BoardController extends Controller
{
    public function __construct(
        private readonly CashierBoardService $board,
    ) {}

    public function index(BoardIndexRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->loadMissing('restaurant');

        return response()->json([
            'currency' => $user->restaurant->currency->value,
            'server_time' => now()->toIso8601String(),
            'cards' => $this->board->board($user->restaurant, $request->scope(), $request->occupancy()),
        ]);
    }
}
