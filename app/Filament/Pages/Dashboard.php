<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Widgets\AccountOverview;
use App\Filament\Widgets\LatestOrders;
use App\Filament\Widgets\NeedsAttentionSessions;
use App\Filament\Widgets\PlatformStats;
use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;

class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            Action::make('newRestaurant')
                ->label('New restaurant')
                ->url(fn (): string => CreateRestaurant::getUrl())
                ->icon(Heroicon::OutlinedPlus)
                ->color('primary'),
        ];
    }

    public function getWidgets(): array
    {
        return [
            AccountOverview::class,
            PlatformStats::class,
            LatestOrders::class,
            NeedsAttentionSessions::class,
        ];
    }

    /**
     * @return array<string, int>
     */
    public function getColumns(): int|array
    {
        return [
            'default' => 1,
            'lg' => 2,
        ];
    }
}
