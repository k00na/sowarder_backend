<?php

namespace App\Filament\App\Resources\TableSessions;

use App\Enums\SessionStatus;
use App\Filament\App\Resources\TableSessions\Pages\ManageTableSessions;
use App\Models\TableSession;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class TableSessionResource extends Resource
{
    protected static ?string $model = TableSession::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTableCells;

    protected static string|UnitEnum|null $navigationGroup = 'History';

    protected static ?string $navigationLabel = 'Sessions';

    protected static ?string $modelLabel = 'table session';

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?int $navigationSort = 40;

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
                    ->label('Ended with unacknowledged orders')
                    ->boolean(),
                TextEntry::make('unacknowledged_count_at_end')
                    ->numeric()
                    ->placeholder('-'),
                Section::make('Orders in this session')
                    ->schema([
                        RepeatableEntry::make('orders')
                            ->label('')
                            ->schema([
                                TextEntry::make('order_reference')
                                    ->label('Reference'),
                                TextEntry::make('submitted_at')
                                    ->dateTime(),
                                TextEntry::make('acknowledged_at')
                                    ->dateTime()
                                    ->placeholder('Unacknowledged'),
                            ])
                            ->columns(3),
                    ]),
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
                SelectFilter::make('status')
                    ->options(SessionStatus::class),
                Filter::make('today')
                    ->label('Today only')
                    ->toggle()
                    ->query(function (Builder $query): Builder {
                        $timezone = Filament::getTenant()?->timezone ?? config('app.timezone');

                        return $query->whereBetween('opened_at', [
                            now($timezone)->startOfDay()->utc(),
                            now($timezone)->endOfDay()->utc(),
                        ]);
                    }),
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
