<?php

namespace App\Http\Controllers\Api\V1\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Cashier\EndSessionRequest;
use App\Models\TableSession;
use App\Services\CashierBoardService;
use App\Services\SessionLifecycleService;
use Illuminate\Http\JsonResponse;

class SessionController extends Controller
{
    public function __construct(
        private readonly CashierBoardService $board,
        private readonly SessionLifecycleService $lifecycle,
    ) {}

    public function show(TableSession $tableSession): JsonResponse
    {
        $this->authorize('view', $tableSession);

        return response()->json($this->board->sessionDetail($tableSession));
    }

    public function accept(TableSession $tableSession): JsonResponse
    {
        $this->authorize('acknowledge', $tableSession);

        $session = $this->lifecycle->acceptOrders($tableSession);

        return response()->json($this->board->sessionDetail($session));
    }

    public function end(EndSessionRequest $request, TableSession $tableSession): JsonResponse
    {
        $this->authorize('endSession', $tableSession);

        $session = $this->lifecycle->endSession($tableSession, $request->confirmUnacknowledged());

        return response()->json($this->board->sessionDetail($session));
    }
}
