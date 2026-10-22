<?php

namespace App\Filament\Resources\Restaurants\Pages;

use App\Enums\RestaurantStatus;
use App\Filament\Resources\Restaurants\RestaurantResource;
use App\Filament\Widgets\RestaurantStats;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListRestaurants extends ListRecords
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            RestaurantStats::class,
        ];
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'enabled' => Tab::make('Enabled')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', RestaurantStatus::Enabled)),
            'disabled' => Tab::make('Disabled')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', RestaurantStatus::Disabled)),
        ];
    }
}
