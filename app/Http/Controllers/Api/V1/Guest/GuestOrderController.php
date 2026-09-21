<?php

namespace App\Http\Controllers\Api\V1\Guest;

use App\Events\OrderReceived;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Guest\SubmitGuestOrderRequest;
use App\Models\OrderLine;
use App\Services\DiningTableTokenResolver;
use App\Services\GuestOrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class GuestOrderController extends Controller
{
    public function __construct(
        private readonly DiningTableTokenResolver $tokenResolver,
        private readonly GuestOrderService $guestOrders,
    ) {}

    public function store(string $tableToken, SubmitGuestOrderRequest $request): JsonResponse
    {
        $table = $this->tokenResolver->resolve($tableToken);

        $order = $this->guestOrders->submit($table, $request->validated());

        OrderReceived::dispatch($order);

        $totalMinor = $order->lines->sum(fn (OrderLine $line): int => $line->lineTotalMinor());

        return response()->json([
            'order_reference' => $order->order_reference,
            'session_id' => $order->table_session_id,
            'currency' => $table->restaurant->currency->value,
            'total_minor' => $totalMinor,
            'acknowledgement' => 'Order received. Please pay at the counter.',
        ], Response::HTTP_CREATED);
    }
}
