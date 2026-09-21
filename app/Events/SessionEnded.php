<?php

namespace App\Events;

use App\Models\TableSession;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched after a cashier's End Session commits.
 *
 * Payload shape matches `docs/realtime-events.md`'s `SessionEnded` event.
 */
class SessionEnded implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public readonly TableSession $session,
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
        return 'SessionEnded';
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
        $session = $this->session->loadMissing('diningTable');

        return [
            'event' => 'SessionEnded',
            'restaurant_id' => (string) $session->restaurant_id,
            'session_id' => $session->id,
            'table_id' => (string) $session->dining_table_id,
            'table_label' => $session->diningTable->label,
            'ended_at' => $session->ended_at?->toIso8601String(),
            'ended_with_unacknowledged' => (bool) $session->ended_with_unacknowledged,
            'unacknowledged_count_at_end' => $session->unacknowledged_count_at_end ?? 0,
            'board_card_state' => 'vacant',
        ];
    }
}
