<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\TableSessionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'restaurant_id',
    'dining_table_id',
    'status',
    'opened_at',
    'ended_at',
    'ended_with_unacknowledged',
    'unacknowledged_count_at_end',
])]
class TableSession extends Model
{
    /** @use HasFactory<TableSessionFactory> */
    use BelongsToRestaurant, HasFactory, HasUuids;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'open',
        'ended_with_unacknowledged' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SessionStatus::class,
            'opened_at' => 'datetime',
            'ended_at' => 'datetime',
            'ended_with_unacknowledged' => 'boolean',
            'unacknowledged_count_at_end' => 'integer',
        ];
    }

    public function isOpen(): bool
    {
        return $this->status === SessionStatus::Open;
    }

    public function diningTable(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
