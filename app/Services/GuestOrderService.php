<?php

namespace App\Services;

use App\Exceptions\IdempotencyConflictException;
use App\Exceptions\SessionChangedException;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GuestOrderService
{
    private const MAX_REFERENCE_ATTEMPTS = 5;

    public function __construct(
        private readonly TableSessionResolver $sessionResolver,
    ) {}

    /**
     * Submit a guest order: idempotent replay/conflict, session attach, fresh
     * price/availability re-validation, and immutable line snapshots, all in
     * one transaction.
     *
     * @param  array{idempotency_key: string, expected_session_id: ?string, special_instructions: ?string, lines: array<int, array{product_id: int|string, quantity: int}>}  $data
     *
     * @throws IdempotencyConflictException
     * @throws SessionChangedException
     * @throws ValidationException
     */
    public function submit(DiningTable $table, array $data): Order
    {
        $this->assertNoDuplicateProducts($data['lines']);

        return DB::transaction(function () use ($table, $data) {
            $existing = $this->findByIdempotencyKey($table, $data['idempotency_key']);

            if ($existing !== null) {
                return $this->replayOrConflict($existing, $data);
            }

            $session = $this->sessionResolver->resolveForOrder($table, $data['expected_session_id']);
            $products = $this->resolveOrderableProducts($table, $data['lines']);

            try {
                return $this->createOrder($table, $session->id, $data, $products);
            } catch (QueryException $exception) {
                if (! $this->isIdempotencyKeyViolation($exception)) {
                    throw $exception;
                }

                // Raced with another request using the same key inside the
                // same transaction window — the unique index is the source
                // of truth, so re-fetch and treat it as a replay/conflict.
                $raced = $this->findByIdempotencyKey($table, $data['idempotency_key']);

                if ($raced === null) {
                    throw $exception;
                }

                return $this->replayOrConflict($raced, $data);
            }
        });
    }

    private function findByIdempotencyKey(DiningTable $table, string $idempotencyKey): ?Order
    {
        return Order::query()
            ->where('restaurant_id', $table->restaurant_id)
            ->where('idempotency_key', $idempotencyKey)
            ->with('lines')
            ->first();
    }

    /**
     * @param  array{special_instructions: ?string, lines: array<int, array{product_id: int|string, quantity: int}>}  $data
     */
    private function replayOrConflict(Order $existing, array $data): Order
    {
        if ($this->matchesBody($existing, $data)) {
            return $existing;
        }

        throw new IdempotencyConflictException;
    }

    /**
     * @param  array{special_instructions: ?string, lines: array<int, array{product_id: int|string, quantity: int}>}  $data
     */
    private function matchesBody(Order $existing, array $data): bool
    {
        if ($existing->instructions !== ($data['special_instructions'] ?? null)) {
            return false;
        }

        $existingLines = $existing->lines
            ->mapWithKeys(fn (OrderLine $line): array => [(string) $line->product_id => $line->quantity])
            ->sortKeys();

        $requestedLines = collect($data['lines'])
            ->mapWithKeys(fn (array $line): array => [(string) $line['product_id'] => (int) $line['quantity']])
            ->sortKeys();

        return $existingLines->all() === $requestedLines->all();
    }

    /**
     * @param  array<int, array{product_id: int|string, quantity: int}>  $lines
     * @return Collection<int, Product>
     *
     * @throws ValidationException
     */
    private function resolveOrderableProducts(DiningTable $table, array $lines): Collection
    {
        $productIds = collect($lines)->pluck('product_id')->map(fn ($id): int => (int) $id)->unique();

        $products = Product::query()
            ->where('restaurant_id', $table->restaurant_id)
            ->whereIn('id', $productIds)
            ->get()
            ->keyBy('id');

        foreach ($productIds as $productId) {
            $product = $products->get($productId);

            if ($product === null) {
                throw ValidationException::withMessages([
                    'lines' => ["Product {$productId} is not available for this restaurant."],
                ]);
            }

            if (! $product->is_available) {
                throw ValidationException::withMessages([
                    'lines' => ["Product \"{$product->name}\" is no longer available."],
                ]);
            }
        }

        return $products;
    }

    /**
     * @param  array{idempotency_key: string, special_instructions: ?string, lines: array<int, array{product_id: int|string, quantity: int}>}  $data
     * @param  Collection<int, Product>  $products
     */
    private function createOrder(DiningTable $table, string $sessionId, array $data, Collection $products): Order
    {
        $currency = $table->restaurant->currency;

        $order = $this->createOrderWithUniqueReference($table, $sessionId, $data);

        foreach ($data['lines'] as $line) {
            $product = $products->get((int) $line['product_id']);

            OrderLine::query()->create([
                'order_id' => $order->id,
                'product_id' => $product->id,
                'name' => $product->name,
                'unit_price_minor' => $product->price_minor,
                'currency' => $currency,
                'quantity' => $line['quantity'],
            ]);
        }

        return $order->fresh('lines');
    }

    /**
     * @param  array{idempotency_key: string, special_instructions: ?string}  $data
     */
    private function createOrderWithUniqueReference(DiningTable $table, string $sessionId, array $data): Order
    {
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return Order::query()->create([
                    'restaurant_id' => $table->restaurant_id,
                    'dining_table_id' => $table->id,
                    'table_session_id' => $sessionId,
                    'order_reference' => Str::upper(Str::random(8)),
                    'idempotency_key' => $data['idempotency_key'],
                    'instructions' => $data['special_instructions'] ?? null,
                    'submitted_at' => now(),
                ]);
            } catch (QueryException $exception) {
                if ($this->isIdempotencyKeyViolation($exception) || ! $this->isOrderReferenceViolation($exception) || $attempt >= self::MAX_REFERENCE_ATTEMPTS) {
                    throw $exception;
                }
            }
        }
    }

    /**
     * @param  array<int, array{product_id: int|string, quantity: int}>  $lines
     *
     * @throws ValidationException
     */
    private function assertNoDuplicateProducts(array $lines): void
    {
        $productIds = collect($lines)->pluck('product_id')->map(fn ($id): string => (string) $id);

        if ($productIds->duplicates()->isNotEmpty()) {
            throw ValidationException::withMessages([
                'lines' => ['Each product may only appear once per order.'],
            ]);
        }
    }

    private function isIdempotencyKeyViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'orders_restaurant_id_idempotency_key_unique');
    }

    private function isOrderReferenceViolation(QueryException $exception): bool
    {
        return str_contains($exception->getMessage(), 'orders_order_reference_unique');
    }
}
