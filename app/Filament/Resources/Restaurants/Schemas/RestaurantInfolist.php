<?php

namespace App\Filament\Resources\Restaurants\Schemas;

use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RestaurantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('slug')
                    ->copyable(),
                TextEntry::make('status')
                    ->badge(),
                TextEntry::make('currency')
                    ->badge(),
                TextEntry::make('timezone'),
                ColorEntry::make('primary_color'),
                ColorEntry::make('secondary_color'),
                TextEntry::make('owner.email')
                    ->label('Owner email')
                    ->placeholder('Not invited'),
                TextEntry::make('owner.name')
                    ->label('Owner name')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
