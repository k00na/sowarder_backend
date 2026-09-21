<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a guest order commits successfully.
 *
 * Stage 3 boundary decision (Option A) is now resolved: Stage 4 wires Reverb
 * broadcasting for the payload shape documented in `docs/realtime-events.md`
 * by making this event broadcastable, without touching the order-creation
 * transaction that dispatches it (see `GuestOrderController::store()`).
 */
class OrderReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly Order $order,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant.'.$this->order->restaurant_id)];
    }

    public function broadcastAs(): string
    {
        return 'OrderReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->toPayload();
    }

    /**
     * Payload shape matching `docs/realtime-events.md`'s `OrderReceived` event.
     *
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $order = $this->order->loadMissing(['lines', 'diningTable', 'tableSession.orders.lines']);
        $session = $order->tableSession;

        $orderTotalMinor = $order->lines->sum(fn ($line) => $line->lineTotalMinor());
        $sessionTotalMinor = $session->orders->flatMap(fn (Order $sessionOrder) => $sessionOrder->lines)
            ->sum(fn ($line) => $line->lineTotalMinor());
        $hasUnacknowledged = $session->orders->contains(fn (Order $sessionOrder) => ! $sessionOrder->isAcknowledged());

        return [
            'event' => 'OrderReceived',
            'restaurant_id' => (string) $order->restaurant_id,
            'session_id' => $session->id,
            'table_id' => (string) $order->dining_table_id,
            'table_label' => $order->diningTable->label,
            'order_id' => $order->id,
            'order_reference' => $order->order_reference,
            'submitted_at' => $order->submitted_at->toIso8601String(),
            'currency' => $order->lines->first()?->currency->value,
            'order_total_minor' => $orderTotalMinor,
            'session_total_minor' => $sessionTotalMinor,
            'board_card_state' => $hasUnacknowledged ? 'occupied_unacknowledged' : 'occupied_acknowledged',
        ];
    }
}
