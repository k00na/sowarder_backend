<?php

namespace App\Filament\Resources\TableSessions;

use App\Enums\SessionStatus;
use App\Filament\Resources\TableSessions\Pages\ManageTableSessions;
use App\Models\TableSession;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class TableSessionResource extends Resource
{
    protected static ?string $model = TableSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'Diagnostics';

    protected static ?string $navigationLabel = 'Sessions';

    protected static ?string $modelLabel = 'table session';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('id')
                    ->label('Session ID')
                    ->copyable(),
                TextEntry::make('restaurant.name')
                    ->label('Restaurant'),
                TextEntry::make('diningTable.label')
                    ->label('Table'),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('opened_at')
                    ->dateTime(),
                TextEntry::make('ended_at')
                    ->dateTime()
                    ->placeholder('-'),
                IconEntry::make('ended_with_unacknowledged')
                    ->boolean(),
                TextEntry::make('unacknowledged_count_at_end')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('opened_at', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('Session ID')
                    ->limit(8)
                    ->tooltip(fn (TableSession $record): string => $record->id)
                    ->copyable(),
                TextColumn::make('restaurant.name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('diningTable.label')
                    ->label('Table')
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('opened_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('ended_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('ended_with_unacknowledged')
                    ->label('Ended unacked')
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->relationship('restaurant', 'name')
                    ->label('Restaurant')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options(SessionStatus::class),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTableSessions::route('/'),
        ];
    }
}
