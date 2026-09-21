<?php

namespace App\Models\Concerns;

use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToRestaurant
{
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForRestaurant(Builder $query, Restaurant|int $restaurant): Builder
    {
        $restaurantId = $restaurant instanceof Restaurant ? $restaurant->id : $restaurant;

        return $query->where($this->getTable().'.restaurant_id', $restaurantId);
    }
}
