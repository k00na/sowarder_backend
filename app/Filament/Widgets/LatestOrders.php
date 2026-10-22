<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LatestOrders extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Latest orders')
            ->query(
                Order::query()
                    ->with(['restaurant', 'diningTable'])
                    ->latest('submitted_at')
                    ->limit(8),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('order_reference')
                    ->label('Reference'),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant'),
                TextColumn::make('diningTable.label')
                    ->label('Table'),
                TextColumn::make('submitted_at')
                    ->label('Submitted')
                    ->dateTime(),
                TextColumn::make('acknowledgement')
                    ->label('Status')
                    ->badge()
                    ->state(fn (Order $record): string => $record->isAcknowledged() ? 'Acknowledged' : 'Not acknowledged')
                    ->color(fn (string $state): string => $state === 'Acknowledged' ? 'success' : 'warning'),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema(fn (Schema $schema): Schema => OrderResource::infolist($schema)),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->url(fn (): string => OrderResource::getUrl())
                    ->link(),
            ]);
    }
}
