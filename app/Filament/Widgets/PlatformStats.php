<?php

namespace App\Filament\Widgets;

use App\Enums\SessionStatus;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\TableSession;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformStats extends StatsOverviewWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        return [
            Stat::make('Restaurants', Restaurant::query()->count()),
            Stat::make('Restaurants without owners', Restaurant::query()->whereDoesntHave('owner')->count()),
            Stat::make('Open sessions', TableSession::query()->where('status', SessionStatus::Open)->count()),
            Stat::make('Unacknowledged orders', Order::query()->whereNull('acknowledged_at')->count()),
        ];
    }
}
