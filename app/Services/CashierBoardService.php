<?php

namespace App\Services;

use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Restaurant;
use App\Models\TableSession;
use Illuminate\Support\Collection;

class CashierBoardService
{
    private const ITEM_PREVIEW_LIMIT = 4;

    /**
     * Table-grid board: one card per table. `current` only reflects a
     * currently open session; `today` additionally back-fills a vacant
     * table's informational fields from its most recent session opened
     * today (even if already ended) — `state` always reflects live
     * occupancy only.
     *
     * @return array<int, array<string, mixed>>
     */
    public function board(Restaurant $restaurant, string $scope, string $occupancy): array
    {
        $tables = DiningTable::query()
            ->where('restaurant_id', $restaurant->id)
            ->with(['openSession.orders.lines'])
            ->orderBy('label')
            ->get();

        $todaySessionsByTable = $scope === 'today'
            ? $this->todaySessionsByTable($restaurant)
            : collect();

        $cards = $tables->map(function (DiningTable $table) use ($todaySessionsByTable): array {
            $liveSession = $table->openSession;
            $displaySession = $liveSession ?? $todaySessionsByTable->get($table->id)?->first();

            return $this->buildCard($table, $liveSession, $displaySession);
        });

        return $this->filterByOccupancy($cards, $occupancy)->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function sessionDetail(TableSession $session): array
    {
        $session->loadMissing(['diningTable', 'orders.lines']);

        $orders = $session->orders->sortBy('submitted_at')->values();

        return [
            'session_id' => $session->id,
            'table_label' => $session->diningTable->label,
            'status' => $session->status->value,
            'opened_at' => $session->opened_at?->toIso8601String(),
            'ended_at' => $session->ended_at?->toIso8601String(),
            'ended_with_unacknowledged' => $session->ended_with_unacknowledged,
            'unacknowledged_count_at_end' => $session->unacknowledged_count_at_end,
            'currency' => $this->resolveCurrency($orders),
            'session_total_minor' => $this->sessionTotalMinor($orders),
            'has_unacknowledged_orders' => $this->hasUnacknowledged($orders),
            'orders' => $orders->map(fn (Order $order): array => $this->orderPayload($order))->all(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildCard(DiningTable $table, ?TableSession $liveSession, ?TableSession $displaySession): array
    {
        $orders = $displaySession?->orders ?? collect();

        return [
            'table_id' => (string) $table->id,
            'table_label' => $table->label,
            'state' => $this->deriveState($liveSession),
            'session_id' => $displaySession?->id,
            'opened_at' => $displaySession?->opened_at?->toIso8601String(),
            'session_total_minor' => $displaySession !== null ? $this->sessionTotalMinor($orders) : null,
            'has_unacknowledged_orders' => $this->hasUnacknowledged($orders),
            'item_preview' => $this->itemPreview($orders),
        ];
    }

    private function deriveState(?TableSession $liveSession): string
    {
        if ($liveSession === null) {
            return 'vacant';
        }

        return $this->hasUnacknowledged($liveSession->orders)
            ? 'occupied_unacknowledged'
            : 'occupied_acknowledged';
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function hasUnacknowledged(Collection $orders): bool
    {
        return $orders->contains(fn (Order $order): bool => ! $order->isAcknowledged());
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function sessionTotalMinor(Collection $orders): int
    {
        return $orders->flatMap(fn (Order $order): Collection => $order->lines)
            ->sum(fn (OrderLine $line): int => $line->lineTotalMinor());
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return array<int, string>
     */
    private function itemPreview(Collection $orders): array
    {
        return $orders->sortByDesc('submitted_at')
            ->flatMap(fn (Order $order): Collection => $order->lines)
            ->pluck('name')
            ->unique()
            ->take(self::ITEM_PREVIEW_LIMIT)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function orderPayload(Order $order): array
    {
        return [
            'id' => $order->id,
            'order_reference' => $order->order_reference,
            'submitted_at' => $order->submitted_at?->toIso8601String(),
            'acknowledged_at' => $order->acknowledged_at?->toIso8601String(),
            'special_instructions' => $order->instructions,
            'total_minor' => $order->lines->sum(fn (OrderLine $line): int => $line->lineTotalMinor()),
            'lines' => $order->lines->map(fn (OrderLine $line): array => [
                'quantity' => $line->quantity,
                'product_name' => $line->name,
                'unit_price_minor' => $line->unit_price_minor,
                'currency' => $line->currency->value,
                'line_total_minor' => $line->lineTotalMinor(),
            ])->all(),
        ];
    }

    /**
     * @return Collection<int, Collection<int, TableSession>>
     */
    private function todaySessionsByTable(Restaurant $restaurant): Collection
    {
        return TableSession::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereBetween('opened_at', [
                now($restaurant->timezone)->startOfDay()->utc(),
                now($restaurant->timezone)->endOfDay()->utc(),
            ])
            ->with('orders.lines')
            ->orderByDesc('opened_at')
            ->get()
            ->groupBy('dining_table_id');
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $cards
     * @return Collection<int, array<string, mixed>>
     */
    private function filterByOccupancy(Collection $cards, string $occupancy): Collection
    {
        return match ($occupancy) {
            'occupied' => $cards->reject(fn (array $card): bool => $card['state'] === 'vacant'),
            'vacant' => $cards->filter(fn (array $card): bool => $card['state'] === 'vacant'),
            default => $cards,
        };
    }

    /**
     * @param  Collection<int, Order>  $orders
     */
    private function resolveCurrency(Collection $orders): ?string
    {
        return $orders->flatMap(fn (Order $order): Collection => $order->lines)->first()?->currency->value;
    }
}
