<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Events\OrderAcknowledged;
use App\Events\SessionEnded;
use App\Exceptions\SessionAlreadyEndedException;
use App\Exceptions\UnacknowledgedOrdersRemainException;
use App\Models\Order;
use App\Models\TableSession;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class SessionLifecycleService
{
    /**
     * Accept Order: acknowledge every currently-unacknowledged order on the
     * session in one action.
     *
     * @throws SessionAlreadyEndedException
     */
    public function acceptOrders(TableSession $session): TableSession
    {
        if ($session->status !== SessionStatus::Open) {
            throw new SessionAlreadyEndedException;
        }

        $acknowledgedAt = Date::now();

        $acknowledgedOrderIds = DB::transaction(function () use ($session, $acknowledgedAt): array {
            $unacknowledged = Order::query()
                ->where('table_session_id', $session->id)
                ->whereNull('acknowledged_at')
                ->lockForUpdate()
                ->get();

            if ($unacknowledged->isEmpty()) {
                return [];
            }

            Order::query()
                ->whereIn('id', $unacknowledged->pluck('id'))
                ->update(['acknowledged_at' => $acknowledgedAt]);

            return $unacknowledged->pluck('id')->all();
        });

        if ($acknowledgedOrderIds !== []) {
            OrderAcknowledged::dispatch($session, $acknowledgedOrderIds, $acknowledgedAt);
        }

        return $session->fresh(['diningTable', 'orders.lines']);
    }

    /**
     * End Session: always allowed on an open session. Idempotent when the
     * session is already ended (safe for polling/reconnect retries). Never
     * fakes `acknowledged_at` on orders left unacknowledged.
     *
     * @throws UnacknowledgedOrdersRemainException
     */
    public function endSession(TableSession $session, bool $confirmUnacknowledged): TableSession
    {
        if ($session->status !== SessionStatus::Open) {
            return $session->fresh(['diningTable', 'orders.lines']);
        }

        $endedSession = DB::transaction(function () use ($session, $confirmUnacknowledged): TableSession {
            $unacknowledgedCount = Order::query()
                ->where('table_session_id', $session->id)
                ->whereNull('acknowledged_at')
                ->count();

            if ($unacknowledgedCount > 0 && ! $confirmUnacknowledged) {
                throw new UnacknowledgedOrdersRemainException;
            }

            $session->forceFill([
                'status' => SessionStatus::Ended,
                'ended_at' => Date::now(),
                'ended_with_unacknowledged' => $unacknowledgedCount > 0,
                'unacknowledged_count_at_end' => $unacknowledgedCount,
            ])->save();

            return $session;
        });

        SessionEnded::dispatch($endedSession);

        return $endedSession->fresh(['diningTable', 'orders.lines']);
    }
}
