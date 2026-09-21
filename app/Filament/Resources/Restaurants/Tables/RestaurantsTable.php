<?php

namespace App\Filament\Resources\Restaurants\Tables;

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
                    ->sortable(),
                TextColumn::make('currency')
                    ->badge()
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
