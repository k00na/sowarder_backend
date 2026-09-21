<?php

namespace App\Models;

use App\Enums\Currency;
use App\ValueObjects\Money;
use Database\Factories\OrderLineFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'order_id',
    'product_id',
    'name',
    'unit_price_minor',
    'currency',
    'quantity',
])]
class OrderLine extends Model
{
    /** @use HasFactory<OrderLineFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'unit_price_minor' => 'integer',
            'currency' => Currency::class,
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function unitMoney(): Money
    {
        return Money::of($this->unit_price_minor, $this->currency);
    }

    public function lineTotalMinor(): int
    {
        return $this->unit_price_minor * $this->quantity;
    }
}
