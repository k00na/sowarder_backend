<?php

namespace App\Models;

use App\Enums\SessionStatus;
use App\Models\Concerns\BelongsToRestaurant;
use Database\Factories\DiningTableFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

#[Fillable([
    'restaurant_id',
    'label',
    'token',
])]
class DiningTable extends Model
{
    /** @use HasFactory<DiningTableFactory> */
    use BelongsToRestaurant, HasFactory, SoftDeletes;

    protected static function booted(): void
    {
        static::creating(function (DiningTable $table): void {
            if (blank($table->token)) {
                $table->token = Str::random(48);
            }
        });
    }

    public function tableSessions(): HasMany
    {
        return $this->hasMany(TableSession::class);
    }

    public function openSession(): HasOne
    {
        return $this->hasOne(TableSession::class)->where('status', SessionStatus::Open);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function regenerateToken(): void
    {
        $this->forceFill([
            'token' => Str::random(48),
        ])->save();
    }
}
