<?php

namespace App\Services;

use App\Enums\RestaurantStatus;
use App\Exceptions\RestaurantDisabledException;
use App\Exceptions\TableTokenInvalidException;
use App\Models\DiningTable;

class DiningTableTokenResolver
{
    /**
     * Resolve a public table token to a usable dining table.
     *
     * @throws TableTokenInvalidException
     * @throws RestaurantDisabledException
     */
    public function resolve(string $token): DiningTable
    {
        $table = DiningTable::query()
            ->with('restaurant')
            ->where('token', $token)
            ->first();

        if ($table === null) {
            throw new TableTokenInvalidException;
        }

        if ($table->restaurant === null || $table->restaurant->status === RestaurantStatus::Disabled) {
            throw new RestaurantDisabledException;
        }

        return $table;
    }
}
