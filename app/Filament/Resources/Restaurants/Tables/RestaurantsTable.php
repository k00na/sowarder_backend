<?php

namespace App\Filament\Resources\Restaurants\Tables;

use App\Enums\Currency;
use App\Enums\RestaurantStatus;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (RestaurantStatus $state): string => match ($state) {
                        RestaurantStatus::Enabled => 'success',
                        RestaurantStatus::Disabled => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('currency')
                    ->badge()
                    ->color(fn (Currency $state): string => match ($state) {
                        Currency::Mvr => 'info',
                        Currency::Usd => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('timezone')
                    ->toggleable(),
                ColorColumn::make('primary_color')
                    ->label('Primary'),
                ColorColumn::make('secondary_color')
                    ->label('Secondary')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('owner.email')
                    ->label('Owner')
                    ->placeholder('Not invited')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
