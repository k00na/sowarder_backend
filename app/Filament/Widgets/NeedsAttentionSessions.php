<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\TableSessions\TableSessionResource;
use App\Models\TableSession;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class NeedsAttentionSessions extends TableWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Needs attention')
            ->description('Sessions ended with unacknowledged orders')
            ->query(
                TableSession::query()
                    ->with(['restaurant', 'diningTable'])
                    ->where('ended_with_unacknowledged', true)
                    ->latest('ended_at')
                    ->limit(8),
            )
            ->paginated(false)
            ->columns([
                TextColumn::make('diningTable.label')
                    ->label('Table'),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant'),
                TextColumn::make('unacknowledged_count_at_end')
                    ->label('Unacknowledged')
                    ->numeric(),
                TextColumn::make('ended_at')
                    ->label('Ended')
                    ->dateTime(),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema(fn (Schema $schema): Schema => TableSessionResource::infolist($schema)),
            ])
            ->headerActions([
                Action::make('viewAll')
                    ->label('View all')
                    ->url(fn (): string => TableSessionResource::getUrl())
                    ->link(),
            ]);
    }
}
