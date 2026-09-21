<?php

namespace App\Filament\App\Resources\Orders;

use App\Filament\App\Resources\Orders\Pages\ManageOrders;
use App\Models\Order;
use BackedEnum;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'History';

    protected static ?string $navigationLabel = 'Orders';

    protected static ?string $recordTitleAttribute = 'order_reference';

    protected static ?int $navigationSort = 50;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('order_reference')
                    ->label('Reference'),
                TextEntry::make('diningTable.label')
                    ->label('Table'),
                TextEntry::make('table_session_id')
                    ->label('Session ID')
                    ->copyable(),
                TextEntry::make('instructions')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('acknowledged_at')
                    ->dateTime()
                    ->placeholder('Unacknowledged'),
                TextEntry::make('submitted_at')
                    ->dateTime(),
                Section::make('Line items')
                    ->schema([
                        RepeatableEntry::make('lines')
                            ->label('')
                            ->schema([
                                TextEntry::make('name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->numeric(),
                                TextEntry::make('unit_price_minor')
                                    ->label('Unit price')
                                    ->formatStateUsing(fn (int $state, $record): string => $record->currency->value.' '.number_format($state / 100, 2)),
                                TextEntry::make('line_total')
                                    ->label('Line total')
                                    ->state(fn ($record): string => $record->currency->value.' '.number_format($record->lineTotalMinor() / 100, 2)),
                            ])
                            ->columns(4),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('submitted_at', 'desc')
            ->columns([
                TextColumn::make('order_reference')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('diningTable.label')
                    ->label('Table')
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('acknowledged_at')
                    ->dateTime()
                    ->placeholder('—')
                    ->sortable(),
                IconColumn::make('acknowledged_at')
                    ->label('Acknowledged')
                    ->boolean(),
            ])
            ->filters([
                //
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
            'index' => ManageOrders::route('/'),
        ];
    }
}
