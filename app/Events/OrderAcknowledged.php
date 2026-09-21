<?php

namespace App\Events;

use App\Models\Order;
use App\Models\OrderLine;
use App\Models\TableSession;
use Carbon\CarbonInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Support\Collection;

/**
 * Dispatched after a cashier's Accept Order commits (ack-all-unacked on a session).
 *
 * Payload shape matches `docs/realtime-events.md`'s `OrderAcknowledged` event.
 */
class OrderAcknowledged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    /**
     * @param  array<int, string>  $acknowledgedOrderIds
     */
    public function __construct(
        public readonly TableSession $session,
        public readonly array $acknowledgedOrderIds,
        public readonly CarbonInterface $acknowledgedAt,
    ) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('restaurant.'.$this->session->restaurant_id)];
    }

    public function broadcastAs(): string
    {
        return 'OrderAcknowledged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return $this->toPayload();
    }

    /**
     * @return array<string, mixed>
     */
    public function toPayload(): array
    {
        $session = $this->session->loadMissing(['diningTable', 'orders.lines']);
        $lines = $session->orders->flatMap(fn (Order $order): Collection => $order->lines);

        return [
            'event' => 'OrderAcknowledged',
            'restaurant_id' => (string) $session->restaurant_id,
            'session_id' => $session->id,
            'table_id' => (string) $session->dining_table_id,
            'table_label' => $session->diningTable->label,
            'acknowledged_order_ids' => $this->acknowledgedOrderIds,
            'acknowledged_at' => $this->acknowledgedAt->toIso8601String(),
            'currency' => $lines->first()?->currency->value,
            'session_total_minor' => $lines->sum(fn (OrderLine $line): int => $line->lineTotalMinor()),
            'board_card_state' => 'occupied_acknowledged',
        ];
    }
}
