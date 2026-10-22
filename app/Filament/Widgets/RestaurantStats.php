<?php

namespace App\Filament\Widgets;

use App\Enums\RestaurantStatus;
use App\Models\Restaurant;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RestaurantStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected function getStats(): array
    {
        return [
            Stat::make('Total', Restaurant::query()->count()),
            Stat::make('Enabled', Restaurant::query()->where('status', RestaurantStatus::Enabled)->count())
                ->color('primary'),
            Stat::make('Disabled', Restaurant::query()->where('status', RestaurantStatus::Disabled)->count()),
            Stat::make('No owner', Restaurant::query()->whereDoesntHave('owner')->count()),
        ];
    }
}
