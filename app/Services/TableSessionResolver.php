<?php

namespace App\Services;

use App\Enums\SessionStatus;
use App\Exceptions\SessionChangedException;
use App\Models\DiningTable;
use App\Models\TableSession;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class TableSessionResolver
{
    private const MAX_CREATE_ATTEMPTS = 3;

    /**
     * Resolve the open session for a table according to expected_session_id rules.
     *
     * Scanning/browsing does not create a session — call this only when submitting an order.
     *
     * @throws SessionChangedException
     */
    public function resolveForOrder(DiningTable $table, ?string $expectedSessionId): TableSession
    {
        return DB::transaction(function () use ($table, $expectedSessionId) {
            // Lock the dining table row so concurrent first orders serialize.
            DiningTable::query()->whereKey($table->id)->lockForUpdate()->firstOrFail();

            $open = TableSession::query()
                ->where('dining_table_id', $table->id)
                ->where('status', SessionStatus::Open)
                ->lockForUpdate()
                ->first();

            if ($expectedSessionId === null) {
                return $open ?? $this->createOpenSession($table);
            }

            if ($open !== null && $open->id === $expectedSessionId) {
                return $open;
            }

            throw new SessionChangedException;
        });
    }

    /**
     * Current open session if any (read path — does not create).
     */
    public function currentOpen(DiningTable $table): ?TableSession
    {
        return TableSession::query()
            ->where('dining_table_id', $table->id)
            ->where('status', SessionStatus::Open)
            ->first();
    }

    private function createOpenSession(DiningTable $table): TableSession
    {
        $attempt = 0;

        while ($attempt < self::MAX_CREATE_ATTEMPTS) {
            $attempt++;

            try {
                return TableSession::query()->create([
                    'restaurant_id' => $table->restaurant_id,
                    'dining_table_id' => $table->id,
                    'status' => SessionStatus::Open,
                    'opened_at' => now(),
                ]);
            } catch (QueryException $exception) {
                if (! $this->isOpenSessionUniqueViolation($exception) || $attempt >= self::MAX_CREATE_ATTEMPTS) {
                    throw $exception;
                }

                $existing = TableSession::query()
                    ->where('dining_table_id', $table->id)
                    ->where('status', SessionStatus::Open)
                    ->first();

                if ($existing !== null) {
                    return $existing;
                }
            }
        }

        throw new \RuntimeException('Failed to create or attach open table session.');
    }

    private function isOpenSessionUniqueViolation(QueryException $exception): bool
    {
        $message = $exception->getMessage();

        return str_contains($message, 'table_sessions_one_open_per_dining_table_unique')
            || str_contains($message, 'duplicate key value violates unique constraint');
    }
}
